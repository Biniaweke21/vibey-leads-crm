<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\MeetingClientResponse;
use App\Enums\MeetingStatus;
use App\Enums\PaymentStatus;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\DesignSubmission;
use App\Models\Document;
use App\Models\DocumentPayment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every method is scoped to the authenticated portal user's own client_id —
 * no Policy: cross-tenant access must resolve to 404 (see abort_if calls
 * below), and Laravel's Policy/Gate denial maps to 403, which the IDOR
 * requirement explicitly forbids. Read-only except updateProfile(),
 * addProjectAttachment(), and requestMeeting().
 */
class ClientPortalService
{
    private const DEFAULT_NOTIFICATIONS = [
        'project_updates' => true,
        'invoices' => true,
        'meeting_reminders' => true,
        'marketing' => false,
    ];

    public function __construct(
        private readonly ProjectService $projects,
        private readonly MeetingService $meetings,
        private readonly DocumentService $documents,
    ) {}

    public function dashboard(User $user): array
    {
        $client = $user->client;

        $activeProjects = $client->activeProjects()->orderBy('name')->get();

        $recentDocuments = $client->documents()->with(['lineItems', 'payments'])
            ->latest('updated_at')->limit(5)->get();

        $outstandingInvoices = $client->documents()
            ->where('type', DocumentType::Invoice)
            ->whereIn('status', ['sent', 'overdue'])
            ->with('lineItems')
            ->get();

        $upcomingMeetings = $client->meetings()
            ->where('status', MeetingStatus::Upcoming)
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        $recentActivity = $client->activities()->with('user')->latest()->limit(6)->get();

        return [
            'welcome_name' => $user->name,
            'company' => $client,
            'documents_count' => $client->documents()->count(),
            'active_projects' => $activeProjects,
            'recent_documents' => $recentDocuments,
            'outstanding_invoices' => $outstandingInvoices,
            'outstanding_total' => round($outstandingInvoices->sum(fn (Document $d) => $d->total), 2),
            'upcoming_meetings' => $upcomingMeetings,
            'recent_activity' => $recentActivity,
        ];
    }

    public function projects(User $user, array $filters): LengthAwarePaginator
    {
        $query = Project::query()->where('client_id', $user->client_id);

        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyDateRange($query, 'created_at', $filters['created_from'] ?? null, $filters['created_to'] ?? null);

        $sortBy = in_array($filters['sort_by'] ?? null, ['name', 'progress', 'created_at', 'start_date'], true)
            ? $filters['sort_by'] : 'created_at';
        $query->orderBy($sortBy, $filters['sort_dir'] ?? 'desc');

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    public function showProject(User $user, Project $project): Project
    {
        abort_if($project->client_id !== $user->client_id, 404, 'Resource not found.');

        return $project->load(['members', 'milestones', 'tasks.assignee', 'designSubmissions.submitter', 'designSubmissions.decider', 'attachments.uploader']);
    }

    public function addProjectAttachment(User $user, Project $project, array $data): Attachment
    {
        abort_if($project->client_id !== $user->client_id, 404, 'Resource not found.');

        return $this->projects->addAttachment($project, $data);
    }

    /** Reuses ProjectService::updateDesignSubmission() — same decided_by/decided_at/activity-log behavior as the internal team's review action. */
    public function reviewDesign(User $user, Project $project, DesignSubmission $design, array $data): DesignSubmission
    {
        abort_if($project->client_id !== $user->client_id, 404, 'Resource not found.');
        abort_if($design->project_id !== $project->id, 404, 'Resource not found.');

        return $this->projects->updateDesignSubmission($project, $design, $data);
    }

    public function documents(User $user, array $filters): LengthAwarePaginator
    {
        $query = Document::query()->where('client_id', $user->client_id)->with(['lineItems', 'payments.attachments']);

        $this->applyInList($query, 'type', $filters['type'] ?? null);
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyInList($query, 'project_id', $filters['project_id'] ?? null, 'intval');
        $this->applyDateRange($query, 'created_at', $filters['created_from'] ?? null, $filters['created_to'] ?? null);

        $sortBy = in_array($filters['sort_by'] ?? null, ['created_at', 'due_date', 'status'], true)
            ? $filters['sort_by'] : 'created_at';
        $query->orderBy($sortBy, $filters['sort_dir'] ?? 'desc');

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    public function showDocument(User $user, Document $document): Document
    {
        abort_if($document->client_id !== $user->client_id, 404, 'Resource not found.');

        return $document->load(['lineItems', 'payments.attachments', 'project']);
    }

    /** Reuses DocumentService::approve()/reject() — same DocumentType::Proposal-only guard and activity logging as the internal team's own action. */
    public function approveDocument(User $user, Document $document): Document
    {
        abort_if($document->client_id !== $user->client_id, 404, 'Resource not found.');

        return $this->documents->approve($document);
    }

    public function rejectDocument(User $user, Document $document): Document
    {
        abort_if($document->client_id !== $user->client_id, 404, 'Resource not found.');

        return $this->documents->reject($document);
    }

    /**
     * The client's own proof of a payment they made — always created Pending,
     * so it never counts toward revenue (every revenue query already filters
     * PaymentStatus::Paid) until a team member reviews and approves it via
     * the existing PATCH /documents/{document}/payments/{payment} endpoint.
     */
    public function submitPaymentProof(User $user, Document $document, array $data): DocumentPayment
    {
        abort_if($document->client_id !== $user->client_id, 404, 'Resource not found.');

        if ($document->type !== DocumentType::Invoice) {
            throw ValidationException::withMessages(['document' => ['Payment proof can only be submitted for invoices.']]);
        }

        return DB::transaction(function () use ($document, $data) {
            $payment = $document->payments()->create([
                'amount' => $data['amount'],
                'stage' => $data['stage'],
                'status' => PaymentStatus::Pending,
            ]);

            $payment->attachments()->create([
                'uploaded_by' => auth()->id(),
                'uploaded_by_role' => 'client',
                'name' => $data['name'],
                'mime_type' => $data['mime_type'] ?? null,
                'size' => $data['size'] ?? null,
                'url' => $data['url'] ?? null,
                'secure_url' => $data['secure_url'] ?? null,
                'cloudinary_public_id' => $data['cloudinary_public_id'] ?? null,
            ]);

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Client submitted a payment proof: {$payment->amount} {$document->currency} ({$payment->stage->label()}) — awaiting approval",
            ]);

            return $payment->load('attachments');
        });
    }

    public function meetings(User $user, array $filters): LengthAwarePaginator
    {
        $query = Meeting::query()->where('client_id', $user->client_id)->with(['attendees.user', 'organizer']);

        $when = $filters['when'] ?? null;
        if ($when === 'upcoming') {
            $query->where('status', MeetingStatus::Upcoming);
        } elseif ($when === 'past') {
            $query->where('status', '!=', MeetingStatus::Upcoming);
        }
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyDateRange($query, 'starts_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        // Upcoming reads soonest-first, past reads most-recent-first — matches the frontend's own tabs.
        $defaultDir = $when === 'past' ? 'desc' : 'asc';
        $query->orderBy('starts_at', $filters['sort_dir'] ?? $defaultDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    /** client_id/organizer_id are never taken from client input — always the authenticated user's own client and (if set) their account manager. */
    public function requestMeeting(User $user, array $data): Meeting
    {
        $client = $user->client;

        return $this->meetings->create([
            ...$data,
            'client_id' => $client->id,
            'organizer_id' => $client->account_manager_id,
        ]);
    }

    public function showMeeting(User $user, Meeting $meeting): Meeting
    {
        abort_if($meeting->client_id !== $user->client_id, 404, 'Resource not found.');

        return $meeting->load(['attendees.user', 'organizer', 'project']);
    }

    public function respondToMeeting(User $user, Meeting $meeting, array $data): Meeting
    {
        abort_if($meeting->client_id !== $user->client_id, 404, 'Resource not found.');

        if ($meeting->status !== MeetingStatus::Upcoming) {
            throw ValidationException::withMessages([
                'meeting' => ['You can only respond to an upcoming meeting.'],
            ]);
        }

        $meeting = $this->meetings->respondAsClient($meeting, MeetingClientResponse::from($data['response']), $data['note'] ?? null);

        return $meeting->load(['attendees.user', 'organizer', 'project']);
    }

    /**
     * Projects/Documents/Meetings all log to the shared polymorphic
     * `activities` table (HasCrmRelations) — one query reaches all three
     * plus the Client's own activities. The standalone `payments` table has
     * no activity log of its own, so it's fetched separately and merged.
     */
    public function timeline(User $user, array $filters): Collection
    {
        $client = $user->client;
        $limit = (int) ($filters['limit'] ?? 50);

        $projectIds = $client->projects()->pluck('id');
        $documentIds = $client->documents()->pluck('id');
        $meetingIds = $client->meetings()->pluck('id');

        $activityQuery = Activity::query()->with('user')
            ->where(function (Builder $q) use ($client, $projectIds, $documentIds, $meetingIds) {
                $q->where(fn (Builder $q2) => $q2->where('subject_type', Client::class)->where('subject_id', $client->id))
                    ->orWhere(fn (Builder $q2) => $q2->where('subject_type', Project::class)->whereIn('subject_id', $projectIds))
                    ->orWhere(fn (Builder $q2) => $q2->where('subject_type', Document::class)->whereIn('subject_id', $documentIds))
                    ->orWhere(fn (Builder $q2) => $q2->where('subject_type', Meeting::class)->whereIn('subject_id', $meetingIds));
            });
        $this->applyDateRange($activityQuery, 'created_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $activities = $activityQuery->latest()->limit($limit)->get()->map(fn (Activity $activity) => [
            'module' => class_basename($activity->subject_type),
            'description' => $activity->description,
            'date' => $activity->created_at,
        ]);

        $paymentQuery = DocumentPayment::query()
            ->whereHas('document', fn (Builder $q) => $q->where('client_id', $client->id))
            ->where('status', PaymentStatus::Paid)
            ->with('document:id,title');
        $this->applyDateRange($paymentQuery, 'paid_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $payments = $paymentQuery->latest('paid_at')->limit($limit)->get()->map(fn (DocumentPayment $payment) => [
            'module' => 'Payment',
            'description' => "Payment received: {$payment->document->title} ({$payment->amount})",
            'date' => $payment->paid_at,
        ]);

        // Meetings are also represented directly (not only via their
        // activities log): the demo seeder creates meetings straight
        // through the factory, bypassing MeetingService::create(), so no
        // "Meeting created" activity exists for them — relying solely on
        // the activities table would silently drop meetings from a
        // client's timeline whenever nothing was ever logged for one.
        $meetingQuery = Meeting::query()->where('client_id', $client->id);
        $this->applyDateRange($meetingQuery, 'starts_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $meetings = $meetingQuery->latest('starts_at')->limit($limit)->get()->map(fn (Meeting $meeting) => [
            'module' => 'Meeting',
            'description' => "Meeting: {$meeting->title}",
            'date' => $meeting->starts_at,
        ]);

        return $activities->concat($payments)->concat($meetings)->sortByDesc('date')->values()->take($limit);
    }

    public function profile(User $user): array
    {
        $client = $user->client()->with('contacts')->first();

        return [
            'company' => $client,
            'primary_contact' => $client->contacts->firstWhere('is_primary', true),
            'secondary_contacts' => $client->contacts->where('is_primary', false)->values(),
            'account' => $user,
            'notification_preferences' => Setting::get("client_notifications:{$user->id}", self::DEFAULT_NOTIFICATIONS),
        ];
    }

    /** Only the user's own name/email and their notification prefs — never the Client (company) record. */
    public function updateProfile(User $user, array $data): array
    {
        $user->fill(array_intersect_key($data, array_flip(['name', 'email'])));
        if ($user->isDirty()) {
            $user->save();
        }

        if (isset($data['notification_preferences'])) {
            $current = Setting::get("client_notifications:{$user->id}", self::DEFAULT_NOTIFICATIONS);
            Setting::put("client_notifications:{$user->id}", array_merge($current, $data['notification_preferences']));
        }

        return $this->profile($user);
    }

    private function applyInList(Builder $query, string $column, ?string $value, ?string $cast = null): void
    {
        if (empty($value)) {
            return;
        }

        $values = array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));
        if ($cast) {
            $values = array_map($cast, $values);
        }

        $query->whereIn($column, $values);
    }

    private function applyDateRange(Builder $query, string $column, ?string $from, ?string $to): void
    {
        if (! empty($from)) {
            $query->whereDate($column, '>=', $from);
        }
        if (! empty($to)) {
            $query->whereDate($column, '<=', $to);
        }
    }
}
