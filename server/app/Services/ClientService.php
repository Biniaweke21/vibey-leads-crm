<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Note;
use App\Services\Concerns\HandlesAttachments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ClientService
{
    use HandlesAttachments;

    private const ALLOWED_SORTS = ['name', 'revenue_total', 'created_at', 'updated_at', 'status'];

    /** Requested include name => actual (possibly nested) eager-load path. */
    private const INCLUDE_MAP = [
        'contacts' => 'contacts',
        'projects' => 'projects',
        'documents' => 'documents',
        'meetings' => 'meetings',
        'payments' => 'documentPayments',
        'accountManager' => 'accountManager',
        'portalUser' => 'portalUsers',
        'notes' => 'notes.user',
        'activities' => 'activities.user',
        'attachments' => 'attachments.uploader',
        'reminders' => 'reminders.user',
    ];

    public function list(array $filters): LengthAwarePaginator
    {
        $query = $this->applyAggregates(Client::query());

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyInList($query, 'industry', $filters['industry'] ?? null);
        $this->applyInList($query, 'account_manager_id', $filters['account_manager_id'] ?? null, 'intval');
        $this->applyRevenueRange($query, $filters['revenue_min'] ?? null, $filters['revenue_max'] ?? null);
        $this->applyDateRange($query, $filters['created_from'] ?? null, $filters['created_to'] ?? null);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortBy = in_array($sortBy, self::ALLOWED_SORTS, true) ? $sortBy : 'created_at';
        $query->orderBy($sortBy, $filters['sort_dir'] ?? 'desc');

        if (! empty($filters['with'])) {
            $query->with($this->resolveIncludes($filters['with']));
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    public function show(Client $client, ?string $with = null): Client
    {
        $this->loadAggregates($client);

        if ($with) {
            $client->load($this->resolveIncludes($with));
        }

        return $client;
    }

    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $data['status'] ??= ClientStatus::Active->value;
            $data['last_interaction_at'] ??= now();

            $client = Client::create($data);

            $client->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Became a client',
            ]);

            return $this->loadAggregates($client);
        });
    }

    public function update(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $client->fill($data);
            $statusChanged = $client->isDirty('status');

            $client->last_interaction_at = now();
            $client->save();

            if ($statusChanged) {
                $client->activities()->create([
                    'user_id' => auth()->id(),
                    'description' => "Status changed to {$client->status->label()}",
                ]);
            }

            return $this->loadAggregates($client);
        });
    }

    /**
     * Soft delete only — projects/documents keep their `client_id` FK intact.
     * The DB-level `cascadeOnDelete()` on those tables only fires on a real
     * SQL DELETE, which SoftDeletes never issues (it's an UPDATE).
     */
    public function delete(Client $client): void
    {
        $client->delete();
    }

    public function addContact(Client $client, array $data): ClientContact
    {
        return DB::transaction(function () use ($client, $data) {
            $data['is_primary'] ??= false;

            if ($data['is_primary']) {
                $client->contacts()->update(['is_primary' => false]);
            }

            $contact = $client->contacts()->create($data);

            $client->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Added contact: {$contact->name}",
            ]);
            $client->update(['last_interaction_at' => now()]);

            return $contact;
        });
    }

    public function updateContact(Client $client, ClientContact $contact, array $data): ClientContact
    {
        return DB::transaction(function () use ($client, $contact, $data) {
            if (! empty($data['is_primary'])) {
                $client->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
            }

            $contact->fill($data);
            $contact->save();

            $client->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Updated contact: {$contact->name}",
            ]);
            $client->update(['last_interaction_at' => now()]);

            return $contact;
        });
    }

    // ---------- Notes ----------

    public function addNote(Client $client, string $body): Note
    {
        return DB::transaction(function () use ($client, $body) {
            $note = $client->notes()->create(['user_id' => auth()->id(), 'body' => $body]);

            $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Note added']);

            return $note;
        });
    }

    public function updateNote(Client $client, Note $note, string $body): Note
    {
        return DB::transaction(function () use ($client, $note, $body) {
            $note->body = $body;
            $note->save();

            $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Note edited']);

            return $note;
        });
    }

    public function deleteNote(Client $client, Note $note): void
    {
        DB::transaction(function () use ($client, $note) {
            $note->delete();

            $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Note deleted']);
        });
    }

    /** Revenue (paid payments) + active project count, computed via SQL aggregates — not N+1. */
    private function applyAggregates(Builder $query): Builder
    {
        return $query
            ->withSum('paidDocumentPayments as revenue_total', 'amount')
            ->withCount('activeProjects as active_projects_count');
    }

    private function loadAggregates(Client $client): Client
    {
        $client->loadSum('paidDocumentPayments as revenue_total', 'amount');
        $client->loadCount('activeProjects as active_projects_count');

        return $client;
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        if (empty($term)) {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('industry', 'like', $like)
                ->orWhereHas('contacts', function (Builder $cq) use ($like) {
                    $cq->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
        });
    }

    private function applyRevenueRange(Builder $query, null|string|float $min, null|string|float $max): void
    {
        if ($min !== null) {
            $query->havingRaw('COALESCE(revenue_total, 0) >= ?', [(float) $min]);
        }
        if ($max !== null) {
            $query->havingRaw('COALESCE(revenue_total, 0) <= ?', [(float) $max]);
        }
    }

    private function applyInList(Builder $query, string $column, ?string $value, ?string $cast = null): void
    {
        if (empty($value)) {
            return;
        }

        $values = $this->explode($value);
        if ($cast) {
            $values = array_map($cast, $values);
        }

        $query->whereIn($column, $values);
    }

    private function applyDateRange(Builder $query, ?string $from, ?string $to): void
    {
        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }
        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    private function explode(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));
    }

    private function resolveIncludes(string $with): array
    {
        $requested = $this->explode($with);

        return array_values(array_intersect_key(self::INCLUDE_MAP, array_flip($requested)));
    }
}
