<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meeting\IndexMeetingRequest;
use App\Http\Requests\Meeting\ShowMeetingRequest;
use App\Http\Requests\Meeting\StoreAttachmentRequest;
use App\Http\Requests\Meeting\StoreAttendeeRequest;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\StoreNoteRequest;
use App\Http\Requests\Meeting\UpdateAttendeeRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Requests\Meeting\UpdateNoteRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\MeetingAttendeeResource;
use App\Http\Resources\MeetingCollection;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\NoteResource;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\Note;
use App\Services\MeetingService;
use Illuminate\Http\JsonResponse;

class MeetingController extends Controller
{
    public function __construct(private readonly MeetingService $meetings) {}

    /**
     * GET /api/meetings — search, filter, sort, paginate.
     * Authorization: MeetingPolicy::viewAny (checked in IndexMeetingRequest).
     */
    public function index(IndexMeetingRequest $request): JsonResponse
    {
        $paginator = $this->meetings->list($request->validated());

        $data = (new MeetingCollection($paginator->getCollection()))->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    /**
     * GET /api/meetings/{meeting}
     * Authorization: MeetingPolicy::view (checked in ShowMeetingRequest).
     */
    public function show(ShowMeetingRequest $request, Meeting $meeting): JsonResponse
    {
        $meeting = $this->meetings->show($meeting, $request->validated('with'));

        return $this->success(new MeetingResource($meeting));
    }

    /**
     * POST /api/meetings
     * Authorization: MeetingPolicy::create (checked in StoreMeetingRequest).
     */
    public function store(StoreMeetingRequest $request): JsonResponse
    {
        $meeting = $this->meetings->create($request->validated());

        return $this->success(new MeetingResource($meeting), 'Meeting created successfully.', 201);
    }

    /**
     * PUT/PATCH /api/meetings/{meeting}
     * Authorization: MeetingPolicy::update (checked in UpdateMeetingRequest).
     */
    public function update(UpdateMeetingRequest $request, Meeting $meeting): JsonResponse
    {
        $meeting = $this->meetings->update($meeting, $request->validated());

        return $this->success(new MeetingResource($meeting), 'Meeting updated successfully.');
    }

    /**
     * DELETE /api/meetings/{meeting} — Admin only.
     * Authorization: MeetingPolicy::delete.
     */
    public function destroy(Meeting $meeting): JsonResponse
    {
        $this->authorize('delete', $meeting);

        $this->meetings->delete($meeting);

        return $this->success(null, 'Meeting deleted successfully.');
    }

    // ---------- Attendees ----------

    public function storeAttendee(StoreAttendeeRequest $request, Meeting $meeting): JsonResponse
    {
        $attendee = $this->meetings->addAttendee($meeting, (int) $request->validated('user_id'));

        return $this->success(new MeetingAttendeeResource($attendee), 'Attendee added successfully.', 201);
    }

    public function updateAttendee(UpdateAttendeeRequest $request, Meeting $meeting, MeetingAttendee $attendee): JsonResponse
    {
        abort_if($attendee->meeting_id !== $meeting->id, 404, 'Resource not found.');

        $attendee = $this->meetings->updateAttendee($meeting, $attendee, (int) $request->validated('user_id'));

        return $this->success(new MeetingAttendeeResource($attendee), 'Attendee updated successfully.');
    }

    public function destroyAttendee(Meeting $meeting, MeetingAttendee $attendee): JsonResponse
    {
        $this->authorize('update', $meeting);
        abort_if($attendee->meeting_id !== $meeting->id, 404, 'Resource not found.');

        $this->meetings->removeAttendee($meeting, $attendee);

        return $this->success(null, 'Attendee removed successfully.');
    }

    // ---------- Notes ----------

    public function storeNote(StoreNoteRequest $request, Meeting $meeting): JsonResponse
    {
        $note = $this->meetings->addNote($meeting, $request->validated('body'));

        return $this->success(new NoteResource($note), 'Note added successfully.', 201);
    }

    public function updateNote(UpdateNoteRequest $request, Meeting $meeting, Note $note): JsonResponse
    {
        abort_if($note->noteable_id !== $meeting->id || $note->noteable_type !== Meeting::class, 404, 'Resource not found.');

        $note = $this->meetings->updateNote($meeting, $note, $request->validated('body'));

        return $this->success(new NoteResource($note), 'Note updated successfully.');
    }

    public function destroyNote(Meeting $meeting, Note $note): JsonResponse
    {
        $this->authorize('update', $meeting);
        abort_if($note->noteable_id !== $meeting->id || $note->noteable_type !== Meeting::class, 404, 'Resource not found.');

        $this->meetings->deleteNote($meeting, $note);

        return $this->success(null, 'Note deleted successfully.');
    }

    // ---------- Attachments ----------

    public function storeAttachment(StoreAttachmentRequest $request, Meeting $meeting): JsonResponse
    {
        $attachment = $this->meetings->addAttachment($meeting, $request->validated());

        return $this->success(new AttachmentResource($attachment), 'File attached successfully.', 201);
    }

    public function destroyAttachment(Meeting $meeting, Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $meeting);
        abort_if($attachment->attachable_id !== $meeting->id || $attachment->attachable_type !== Meeting::class, 404, 'Resource not found.');

        $this->meetings->deleteAttachment($meeting, $attachment);

        return $this->success(null, 'File removed successfully.');
    }
}
