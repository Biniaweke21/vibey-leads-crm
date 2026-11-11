<?php

namespace App\Services;

use App\Enums\AiAnalysisStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Reminder;
use App\Services\Concerns\HandlesAttachments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeadService
{
    use HandlesAttachments;

    private const ALLOWED_SORTS = ['created_at', 'updated_at', 'business_name', 'budget', 'status'];

    /** Requested include name => actual (possibly nested) eager-load path. */
    private const INCLUDE_MAP = [
        'assignee' => 'assignee',
        'notes' => 'notes.user',
        'activities' => 'activities.user',
        'reminders' => 'reminders.user',
        'attachments' => 'attachments.uploader',
    ];

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Lead::query();

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyInList($query, 'priority', $filters['priority'] ?? null);
        $this->applyInList($query, 'assigned_to', $filters['assigned_to'] ?? null, 'intval');
        $this->applyInList($query, 'industry', $filters['industry'] ?? null);
        $this->applyInList($query, 'budget', $filters['budget'] ?? null);
        $this->applyServiceFilter($query, $filters['service'] ?? null);
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

    public function show(Lead $lead, ?string $with = null): Lead
    {
        if ($with) {
            $lead->load($this->resolveIncludes($with));
        }

        return $lead;
    }

    public function create(array $data): Lead
    {
        // Not Lead columns — these are the metadata POST /uploads already
        // returned for whatever the submitter attached (logo, brand
        // guidelines, etc.), turned into real Attachment rows below.
        $files = [...($data['design_files'] ?? []), ...($data['general_files'] ?? [])];
        unset($data['design_files'], $data['general_files']);

        return DB::transaction(function () use ($data, $files) {
            $data['status'] ??= LeadStatus::New->value;
            $data['priority'] ??= $this->autoPriority($data['budget'] ?? null)->value;
            $data['ai_analysis_status'] ??= AiAnalysisStatus::Pending->value;
            $data['stage_entered_at'] ??= now();

            $lead = Lead::create($data);

            $lead->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Lead created',
            ]);

            // The submitter isn't authenticated yet (this is the public
            // intake form) — HandlesAttachments::addAttachment() assumes a
            // logged-in user, so attach directly instead.
            foreach ($files as $file) {
                $lead->attachments()->create([
                    'uploaded_by' => auth()->id(),
                    'uploaded_by_role' => auth()->user()?->role->isTeam() ? 'team' : 'client',
                    'name' => $file['name'],
                    'mime_type' => $file['mime_type'] ?? null,
                    'size' => $file['size'] ?? null,
                    'url' => $file['url'] ?? null,
                    'secure_url' => $file['secure_url'] ?? null,
                    'cloudinary_public_id' => $file['cloudinary_public_id'] ?? null,
                ]);
            }

            return $lead;
        });
    }

    public function update(Lead $lead, array $data): Lead
    {
        return DB::transaction(function () use ($lead, $data) {
            $lead->fill($data);
            $statusChanged = $lead->isDirty('status');

            if ($statusChanged) {
                $lead->stage_entered_at = now();
            }

            $lead->save();

            if ($statusChanged) {
                $lead->activities()->create([
                    'user_id' => auth()->id(),
                    'description' => "Stage changed to {$lead->status->label()}",
                ]);
            }

            return $lead;
        });
    }

    public function delete(Lead $lead): void
    {
        $lead->delete();
    }

    // ---------- Reminders ----------

    public function addReminder(Lead $lead, string $title, string $dueAt): Reminder
    {
        return DB::transaction(function () use ($lead, $title, $dueAt) {
            $reminder = $lead->reminders()->create([
                'user_id' => auth()->id(),
                'title' => $title,
                'due_at' => $dueAt,
            ]);

            $lead->activities()->create(['user_id' => auth()->id(), 'description' => "Reminder added: {$title}"]);

            return $reminder;
        });
    }

    // ---------- Notes ----------

    public function addNote(Lead $lead, string $body): Note
    {
        return DB::transaction(function () use ($lead, $body) {
            $note = $lead->notes()->create(['user_id' => auth()->id(), 'body' => $body]);

            $lead->activities()->create(['user_id' => auth()->id(), 'description' => 'Note added']);

            return $note;
        });
    }

    public function updateNote(Lead $lead, Note $note, string $body): Note
    {
        return DB::transaction(function () use ($lead, $note, $body) {
            $note->body = $body;
            $note->save();

            $lead->activities()->create(['user_id' => auth()->id(), 'description' => 'Note edited']);

            return $note;
        });
    }

    public function deleteNote(Lead $lead, Note $note): void
    {
        DB::transaction(function () use ($lead, $note) {
            $note->delete();

            $lead->activities()->create(['user_id' => auth()->id(), 'description' => 'Note deleted']);
        });
    }

    /** Mirrors the frontend's autoPriority() in client/src/stores/leads.ts. */
    private function autoPriority(?string $budget): LeadPriority
    {
        $budget ??= '';

        if (str_contains($budget, '300,000 ETB+') || str_contains($budget, '100,000')) {
            return LeadPriority::Hot;
        }

        if (str_contains($budget, '50,000') || str_contains($budget, '30,000')) {
            return LeadPriority::Warm;
        }

        return LeadPriority::Cold;
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        if (empty($term)) {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('business_name', 'like', $like)
                ->orWhere('company_name', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
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

    private function applyServiceFilter(Builder $query, ?string $value): void
    {
        if (empty($value)) {
            return;
        }

        $services = $this->explode($value);
        $query->where(function (Builder $q) use ($services) {
            foreach ($services as $service) {
                $q->orWhereJsonContains('services', $service);
            }
        });
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
