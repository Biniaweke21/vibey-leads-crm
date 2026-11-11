<?php

namespace App\Services;

use App\Enums\MeetingClientResponse;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Mail\MeetingCancelledMail;
use App\Mail\MeetingClientUnavailableMail;
use App\Mail\MeetingInvitationMail;
use App\Models\Client;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\Note;
use App\Models\User;
use App\Services\Concerns\HandlesAttachments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MeetingService
{
    use HandlesAttachments;

    private const ALLOWED_SORTS = ['starts_at', 'created_at', 'updated_at', 'status', 'client_name'];

    /** Requested include name => actual (possibly nested) eager-load path. */
    private const INCLUDE_MAP = [
        'client' => 'client',
        'project' => 'project',
        'lead' => 'lead',
        'attendees' => 'attendees.user',
        'notes' => 'notes.user',
        'activities' => 'activities.user',
        'attachments' => 'attachments.uploader',
        'reminders' => 'reminders.user',
    ];

    public function __construct(private readonly GoogleCalendarService $googleCalendar) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Meeting::query();
        $this->applyClientNameSubquery($query);

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyInList($query, 'type', $filters['type'] ?? null);
        $this->applyInList($query, 'client_id', $filters['client_id'] ?? null, 'intval');
        $this->applyInList($query, 'project_id', $filters['project_id'] ?? null, 'intval');
        $this->applyInList($query, 'organizer_id', $filters['organizer_id'] ?? null, 'intval');
        $this->applyDateRange($query, $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $sortBy = $filters['sort_by'] ?? 'starts_at';
        $sortBy = in_array($sortBy, self::ALLOWED_SORTS, true) ? $sortBy : 'starts_at';
        $query->orderBy($sortBy, $filters['sort_dir'] ?? 'desc');

        if (! empty($filters['with'])) {
            $query->with($this->resolveIncludes($filters['with']));
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    public function show(Meeting $meeting, ?string $with = null): Meeting
    {
        if ($with) {
            $meeting->load($this->resolveIncludes($with));
        }

        // Lazily surface the "should this have auto-completed by now" case on
        // read, mirroring Document's overdue-invoice pattern from Phase 6.
        $this->autoCompleteIfDue($meeting);

        return $meeting;
    }

    public function create(array $data): Meeting
    {
        $meeting = DB::transaction(function () use ($data) {
            $data['status'] ??= MeetingStatus::Upcoming->value;
            $data['type'] ??= MeetingType::Other->value;
            $data['organizer_id'] ??= auth()->id();

            $meeting = Meeting::create($data);

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Meeting created',
            ]);

            return $meeting;
        });

        // Only fill in a Meet link when nobody typed one in by hand, and
        // only if the company's Google account is actually connected.
        if (! $meeting->meeting_url && $this->googleCalendar->isConnected()) {
            $link = $this->googleCalendar->createMeetingEvent($meeting);
            if ($link) {
                $meeting->update(['meeting_url' => $link]);
            }
        }

        $this->inviteClientContact($meeting);

        return $meeting;
    }

    public function update(Meeting $meeting, array $data): Meeting
    {
        $statusChanged = false;
        $newStatus = $meeting->status;

        $meeting = DB::transaction(function () use ($meeting, $data, &$statusChanged, &$newStatus) {
            $meeting->fill($data);
            $statusChanged = $meeting->isDirty('status');
            $newStatus = $meeting->status;

            $meeting->save();

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => $statusChanged ? $this->statusChangeMessage($newStatus) : 'Meeting updated',
            ]);

            return $meeting;
        });

        if ($statusChanged && $newStatus === MeetingStatus::Cancelled) {
            $this->notifyCancellation($meeting);
        }

        return $meeting;
    }

    /** The client's own yes/no to a booked meeting. Unavailable notifies the organizer + attendees — available doesn't (nobody asked to be paged for good news). */
    public function respondAsClient(Meeting $meeting, MeetingClientResponse $response, ?string $note = null): Meeting
    {
        $meeting = DB::transaction(function () use ($meeting, $response, $note) {
            $meeting->client_response = $response;
            $meeting->client_response_note = $note;
            $meeting->client_responded_at = now();
            $meeting->save();

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => $response === MeetingClientResponse::Available
                    ? 'Client confirmed availability'
                    : 'Client marked as unavailable'.($note ? ": {$note}" : ''),
            ]);

            return $meeting;
        });

        if ($response === MeetingClientResponse::Unavailable) {
            $this->notifyUnavailability($meeting);
        }

        return $meeting;
    }

    /**
     * Soft delete only — client/project/documents keep their FK intact. The
     * DB-level `cascadeOnDelete()` on the client_id/project_id FKs only
     * fires on a real SQL DELETE, which SoftDeletes never issues.
     */
    public function delete(Meeting $meeting): void
    {
        $meeting->delete();
    }

    // ---------- Notes ----------

    public function addNote(Meeting $meeting, string $body): Note
    {
        return DB::transaction(function () use ($meeting, $body) {
            $note = $meeting->notes()->create(['user_id' => auth()->id(), 'body' => $body]);

            $meeting->activities()->create(['user_id' => auth()->id(), 'description' => 'Note added']);

            return $note;
        });
    }

    public function updateNote(Meeting $meeting, Note $note, string $body): Note
    {
        return DB::transaction(function () use ($meeting, $note, $body) {
            $note->body = $body;
            $note->save();

            $meeting->activities()->create(['user_id' => auth()->id(), 'description' => 'Note edited']);

            return $note;
        });
    }

    public function deleteNote(Meeting $meeting, Note $note): void
    {
        DB::transaction(function () use ($meeting, $note) {
            $note->delete();

            $meeting->activities()->create(['user_id' => auth()->id(), 'description' => 'Note deleted']);
        });
    }

    // ---------- Attendees ----------

    public function addAttendee(Meeting $meeting, int $userId): MeetingAttendee
    {
        [$attendee, $user] = DB::transaction(function () use ($meeting, $userId) {
            $user = User::findOrFail($userId);

            $attendee = $meeting->attendees()->create([
                'user_id' => $user->id,
                'name' => $user->name,
            ]);

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Attendee added: {$user->name}",
            ]);

            return [$attendee, $user];
        });

        if ($user->email) {
            $this->sendMailBestEffort(
                fn () => Mail::to($user->email)->send(new MeetingInvitationMail($meeting, $user->name, $this->portalUrl('/login'))),
                $meeting,
                "Could not email meeting invitation to {$user->email}",
            );
        }

        return $attendee;
    }

    public function updateAttendee(Meeting $meeting, MeetingAttendee $attendee, int $userId): MeetingAttendee
    {
        return DB::transaction(function () use ($meeting, $attendee, $userId) {
            $user = User::findOrFail($userId);
            $oldName = $attendee->name;

            $attendee->user_id = $user->id;
            $attendee->name = $user->name;
            $attendee->save();

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Attendee updated: {$oldName} → {$user->name}",
            ]);

            return $attendee;
        });
    }

    public function removeAttendee(Meeting $meeting, MeetingAttendee $attendee): void
    {
        DB::transaction(function () use ($meeting, $attendee) {
            $name = $attendee->name;
            $attendee->delete();

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Attendee removed: {$name}",
            ]);
        });
    }

    /** Emails the client's primary contact a meeting invitation, if the meeting is tied to a client with one on file. */
    private function inviteClientContact(Meeting $meeting): void
    {
        if (! $meeting->client_id) {
            return;
        }

        $client = $meeting->client ?? Client::find($meeting->client_id);
        $contact = $client?->contacts()->where('is_primary', true)->first()
            ?? $client?->contacts()->first();

        if ($contact && $contact->email) {
            $this->sendMailBestEffort(
                fn () => Mail::to($contact->email)->send(new MeetingInvitationMail($meeting, $contact->name, $this->portalUrl('/client/login'))),
                $meeting,
                "Could not email meeting invitation to {$contact->email}",
            );
        }
    }

    /** Same recipient set as inviteClientContact() (attendees + the client's primary contact), mirrored for cancellations. Keyed by email to dedupe. */
    private function notifyCancellation(Meeting $meeting): void
    {
        $recipients = collect();

        foreach ($meeting->attendees()->with('user')->get() as $attendee) {
            if ($attendee->user?->email) {
                $recipients->put($attendee->user->email, ['name' => $attendee->name, 'portalUrl' => $this->portalUrl('/login')]);
            }
        }

        if ($meeting->client_id) {
            $client = $meeting->client ?? Client::find($meeting->client_id);
            $contact = $client?->contacts()->where('is_primary', true)->first()
                ?? $client?->contacts()->first();

            if ($contact && $contact->email) {
                $recipients->put($contact->email, ['name' => $contact->name, 'portalUrl' => $this->portalUrl('/client/login')]);
            }
        }

        foreach ($recipients as $email => $recipient) {
            $this->sendMailBestEffort(
                fn () => Mail::to($email)->send(new MeetingCancelledMail($meeting, $recipient['name'], $recipient['portalUrl'])),
                $meeting,
                "Could not email cancellation notice to {$email}",
            );
        }
    }

    /** Team-only (organizer + attendees) — the client is the one who just responded, so they're never a recipient here. */
    private function notifyUnavailability(Meeting $meeting): void
    {
        $recipients = collect();

        if ($meeting->organizer?->email) {
            $recipients->put($meeting->organizer->email, $meeting->organizer->name);
        }

        foreach ($meeting->attendees()->with('user')->get() as $attendee) {
            if ($attendee->user?->email) {
                $recipients->put($attendee->user->email, $attendee->name);
            }
        }

        foreach ($recipients as $email => $name) {
            $this->sendMailBestEffort(
                fn () => Mail::to($email)->send(new MeetingClientUnavailableMail($meeting, $name, $this->portalUrl('/login'))),
                $meeting,
                "Could not email unavailability notice to {$email}",
            );
        }
    }

    private function portalUrl(string $path): string
    {
        return rtrim(config('services.frontend.url', 'http://localhost:5173'), '/').$path;
    }

    /**
     * Runs a mail dispatch without letting delivery failures (e.g. bad SMTP
     * credentials) fail the surrounding request — the meeting/attendee this
     * is attached to has already been persisted by this point.
     */
    private function sendMailBestEffort(callable $dispatch, Meeting $meeting, string $failureMessage): void
    {
        try {
            $dispatch();
        } catch (\Throwable $e) {
            Log::warning("{$failureMessage}: {$e->getMessage()}");

            $meeting->activities()->create([
                'user_id' => auth()->id(),
                'description' => "{$failureMessage} (mail delivery failed)",
            ]);
        }
    }

    private function statusChangeMessage(MeetingStatus $status): string
    {
        return match ($status) {
            MeetingStatus::Cancelled => 'Meeting cancelled',
            MeetingStatus::Completed => 'Meeting marked as completed',
            MeetingStatus::Upcoming => 'Status changed to Upcoming',
        };
    }

    private function autoCompleteIfDue(Meeting $meeting): void
    {
        if (! $meeting->isDueForCompletion()) {
            return;
        }

        $meeting->status = MeetingStatus::Completed;
        $meeting->saveQuietly();

        $meeting->activities()->create([
            'user_id' => auth()->id(),
            'description' => 'Meeting automatically marked as completed',
        ]);
    }

    private function applyClientNameSubquery(Builder $query): void
    {
        $query->addSelect([
            'client_name' => Client::select('name')->whereColumn('id', 'meetings.client_id'),
        ]);
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        if (empty($term)) {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhereHas('client', fn (Builder $cq) => $cq->where('name', 'like', $like))
                ->orWhereHas('project', fn (Builder $pq) => $pq->where('name', 'like', $like))
                ->orWhereHas('lead', fn (Builder $lq) => $lq->where('full_name', 'like', $like)->orWhere('business_name', 'like', $like))
                ->orWhereHas('organizer', fn (Builder $oq) => $oq->where('name', 'like', $like));
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

    private function applyDateRange(Builder $query, ?string $from, ?string $to): void
    {
        if (! empty($from)) {
            $query->whereDate('starts_at', '>=', $from);
        }
        if (! empty($to)) {
            $query->whereDate('starts_at', '<=', $to);
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
