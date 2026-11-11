<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentLineItem;
use App\Models\DocumentPayment;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pure read/aggregate layer over existing CRM tables — no analytics tables,
 * nothing cached or stored. Every method takes the same $filters shape
 * (see AnalyticsRequest) and applies only what's meaningful for its query.
 */
class AnalyticsService
{
    public function dashboard(array $filters): array
    {
        [$from, $to] = $this->resolveRange($filters);

        $totalLeads = Lead::query()->whereBetween('created_at', [$from, $to])->count();

        // Current-state snapshots, not range-bound — mirrors the frontend
        // reference implementation's own KPI definitions.
        $activeClients = Client::query()->where('status', ClientStatus::Active)->count();
        $activeProjects = Project::query()->where('status', '!=', ProjectStatus::Completed)->count();

        $totalRevenue = (float) DocumentPayment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $decided = Document::query()
            ->where('type', DocumentType::Proposal)
            ->whereIn('status', [DocumentStatus::Approved, DocumentStatus::Rejected])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved, COUNT(*) as total', [DocumentStatus::Approved->value])
            ->first();
        $proposalConversionRate = $decided && $decided->total > 0
            ? (int) round(($decided->approved / $decided->total) * 100)
            : 0;

        $invoiceIds = Document::query()
            ->where('type', DocumentType::Invoice)
            ->whereBetween('created_at', [$from, $to])
            ->pluck('id');
        // Aliased as grand_total, not total — DocumentLineItem defines a
        // `total` Attribute accessor, which would silently shadow a raw
        // `total` column on the hydrated model and always read as 0.
        $totalInvoiced = (float) DocumentLineItem::query()
            ->whereIn('document_id', $invoiceIds)
            ->selectRaw('COALESCE(SUM((quantity * unit_price - discount) * (1 + tax_rate / 100)), 0) as grand_total')
            ->value('grand_total');
        $totalCollected = (float) DocumentPayment::query()
            ->whereIn('document_id', $invoiceIds)
            ->where('status', PaymentStatus::Paid)
            ->sum('amount');
        $invoiceCollectionRate = $totalInvoiced > 0 ? (int) round(($totalCollected / $totalInvoiced) * 100) : 0;

        $averageProjectValue = (float) (Project::query()->whereBetween('created_at', [$from, $to])->avg('budget') ?? 0);

        // Lead.budget is a free-text range ("50,000 – 100,000 ETB"), not a
        // number, so it can't be AVG()'d — read instead as revenue earned
        // per lead acquired in the range (a standard blended-value metric).
        $averageLeadValue = $totalLeads > 0 ? round($totalRevenue / $totalLeads, 2) : 0.0;

        return [
            'total_leads' => $totalLeads,
            'active_clients' => $activeClients,
            'active_projects' => $activeProjects,
            'total_revenue' => round($totalRevenue, 2),
            'proposal_conversion_rate' => $proposalConversionRate,
            'invoice_collection_rate' => $invoiceCollectionRate,
            'average_project_value' => round($averageProjectValue, 2),
            'average_lead_value' => $averageLeadValue,
        ];
    }

    public function revenueTrend(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);
        $format = $this->bucketFormat($filters['group'] ?? 'day');

        return DocumentPayment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(paid_at, '{$format}') as label, SUM(amount) as value")
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (float) $row->value]);
    }

    public function leadsTrend(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);
        $format = $this->bucketFormat($filters['group'] ?? 'day');

        $query = Lead::query()->whereBetween('created_at', [$from, $to]);
        $this->applyInList($query, 'assigned_to', $filters['assigned_to'] ?? null);

        return $query
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value]);
    }

    public function pipeline(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);

        $query = Lead::query()->whereBetween('created_at', [$from, $to]);
        $this->applyInList($query, 'assigned_to', $filters['assigned_to'] ?? null);

        $counts = $query->select('status')->selectRaw('COUNT(*) as value')
            ->groupBy('status')->pluck('value', 'status');

        return collect(LeadStatus::cases())->map(fn (LeadStatus $status) => [
            'label' => $status->label(),
            'value' => (int) ($counts[$status->value] ?? 0),
        ]);
    }

    /** Bounded to the 10 known service options — COUNT() per option, not a PHP loop over lead rows. */
    public function serviceDistribution(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);
        $industry = $filters['industry'] ?? null;

        return collect(Lead::SERVICE_OPTIONS)
            ->map(function (string $service) use ($from, $to, $industry) {
                $query = Lead::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereJsonContains('services', $service);

                if (! empty($industry)) {
                    $query->where('industry', $industry);
                }

                return ['label' => $service, 'value' => $query->count()];
            })
            ->sortByDesc('value')
            ->values();
    }

    public function projectStatusDistribution(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);

        $query = Project::query()->whereBetween('created_at', [$from, $to]);
        $this->applyInList($query, 'client_id', $filters['client_id'] ?? null);

        $counts = $query->select('status')->selectRaw('COUNT(*) as value')
            ->groupBy('status')->pluck('value', 'status');

        return collect(ProjectStatus::cases())->map(fn (ProjectStatus $status) => [
            'label' => $status->label(),
            'value' => (int) ($counts[$status->value] ?? 0),
        ]);
    }

    /** Reuses Client::paidDocumentPayments — the same revenue relation ClientService already sums for the Clients list. */
    public function revenueByClient(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);
        $limit = (int) ($filters['limit'] ?? 10);

        return Client::query()
            ->withSum(['paidDocumentPayments' => fn (Builder $q) => $q->whereBetween('paid_at', [$from, $to])], 'amount')
            ->orderByDesc('paid_document_payments_sum_amount')
            ->limit($limit)
            ->get()
            ->map(fn (Client $client) => [
                'label' => $client->name,
                'value' => (float) ($client->paid_document_payments_sum_amount ?? 0),
            ]);
    }

    public function proposalSuccess(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);

        $query = Document::query()->where('type', DocumentType::Proposal)->whereBetween('created_at', [$from, $to]);
        $this->applyInList($query, 'client_id', $filters['client_id'] ?? null);
        $this->applyInList($query, 'project_id', $filters['project_id'] ?? null);

        $row = $query->selectRaw(
            'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
             SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending',
            [
                DocumentStatus::Approved->value,
                DocumentStatus::Rejected->value,
                DocumentStatus::Draft->value,
                DocumentStatus::Sent->value,
            ],
        )->first();

        return collect([
            ['label' => 'Approved', 'value' => (int) $row->approved],
            ['label' => 'Rejected', 'value' => (int) $row->rejected],
            ['label' => 'Pending', 'value' => (int) $row->pending],
        ]);
    }

    public function invoiceStatus(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);

        $query = Document::query()->where('type', DocumentType::Invoice)->whereBetween('created_at', [$from, $to]);
        $this->applyInList($query, 'client_id', $filters['client_id'] ?? null);
        $this->applyInList($query, 'project_id', $filters['project_id'] ?? null);

        $counts = $query->select('status')->selectRaw('COUNT(*) as value')
            ->groupBy('status')->pluck('value', 'status');

        return collect(DocumentStatus::forType(DocumentType::Invoice))->map(fn (DocumentStatus $status) => [
            'label' => $status->label(),
            'value' => (int) ($counts[$status->value] ?? 0),
        ]);
    }

    /**
     * "Revenue Managed" has no existing relation to reuse (Client only has
     * accountManager(), not the inverse), so it's a correlated subquery
     * instead of adding a new relation to the User model.
     */
    public function teamPerformance(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $wonStatuses = array_map(fn (LeadStatus $s) => $s->value, LeadStatus::wonStages());
        $teamRoles = array_map(fn (UserRole $r) => $r->value, UserRole::team());

        return User::query()
            ->whereIn('role', $teamRoles)
            ->withCount(['assignedLeads' => fn (Builder $q) => $q->whereBetween('created_at', [$from, $to])])
            ->withCount(['assignedLeads as won_clients_count' => fn (Builder $q) => $q
                ->whereIn('status', $wonStatuses)
                ->whereBetween('created_at', [$from, $to])])
            ->withCount(['projects as active_projects_count' => fn (Builder $q) => $q
                ->where('status', '!=', ProjectStatus::Completed)])
            ->withCount(['tasks as completed_tasks_count' => fn (Builder $q) => $q
                ->where('status', TaskStatus::Done)
                ->whereBetween('updated_at', [$from, $to])])
            ->addSelect(['revenue_managed' => Client::query()
                ->selectRaw('COALESCE(SUM(document_payments.amount), 0)')
                ->join('documents', 'documents.client_id', '=', 'clients.id')
                ->join('document_payments', 'document_payments.document_id', '=', 'documents.id')
                ->where('document_payments.status', PaymentStatus::Paid->value)
                ->whereBetween('document_payments.paid_at', [$from, $to])
                ->whereColumn('clients.account_manager_id', 'users.id'),
            ])
            ->orderBy('assigned_leads_count', $sortDir)
            ->get()
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->label(),
                'assigned_leads' => $user->assigned_leads_count,
                'won_clients' => $user->won_clients_count,
                'active_projects' => $user->active_projects_count,
                'revenue_managed' => (float) $user->revenue_managed,
                'completed_tasks' => $user->completed_tasks_count,
            ]);
    }

    /**
     * Leads/Clients/Projects/Documents/Meetings all share the polymorphic
     * `activities` relation (HasCrmRelations) — AI analysis/proposal
     * generation also logs there (see AiService), so a single query covers
     * everything with no separate merge step needed.
     */
    public function recentActivity(array $filters): Collection
    {
        [$from, $to] = $this->resolveRange($filters);
        $limit = (int) ($filters['limit'] ?? 20);

        $activityQuery = Activity::query()->with(['user', 'subject'])->whereBetween('created_at', [$from, $to]);
        $this->applyInList($activityQuery, 'user_id', $filters['assigned_to'] ?? null);

        return $activityQuery->latest()->limit($limit)->get()
            ->map(fn (Activity $activity) => [
                'module' => $activity->subject_type ? class_basename($activity->subject_type) : 'System',
                'description' => $activity->description,
                'user' => $activity->user?->name,
                'created_at' => $activity->created_at,
            ]);
    }

    private function resolveRange(array $filters): array
    {
        $range = $filters['range'] ?? '30d';

        if ($range === 'custom') {
            return [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ];
        }

        $from = match ($range) {
            '7d' => now()->subDays(7)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            'ytd' => now()->startOfYear(),
            default => now()->subDays(30)->startOfDay(),
        };

        return [$from, now()];
    }

    private function bucketFormat(string $group): string
    {
        return match ($group) {
            'week' => '%x-W%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    private function applyInList(Builder $query, string $column, ?string $value): void
    {
        if (empty($value)) {
            return;
        }

        $ids = array_map('intval', array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== '')));
        $query->whereIn($column, $ids);
    }
}
