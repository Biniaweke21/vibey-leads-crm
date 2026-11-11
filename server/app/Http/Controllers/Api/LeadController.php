<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\ConvertLeadRequest;
use App\Http\Requests\Lead\IndexLeadRequest;
use App\Http\Requests\Lead\ShowLeadRequest;
use App\Http\Requests\Lead\StoreAttachmentRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\StoreNoteRequest;
use App\Http\Requests\Lead\StoreReminderRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Requests\Lead\UpdateNoteRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\LeadCollection;
use App\Http\Resources\LeadResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\ReminderResource;
use App\Models\Attachment;
use App\Models\Lead;
use App\Models\Note;
use App\Services\ClientOnboardingService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leads,
        private readonly ClientOnboardingService $onboarding,
    ) {}

    /**
     * GET /api/leads — search, filter, sort, paginate.
     * Authorization: LeadPolicy::viewAny (checked in IndexLeadRequest).
     */
    public function index(IndexLeadRequest $request): JsonResponse
    {
        $paginator = $this->leads->list($request->validated());

        $data = (new LeadCollection($paginator->getCollection()))->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    /**
     * GET /api/leads/{lead}
     * Authorization: LeadPolicy::view (checked in ShowLeadRequest).
     */
    public function show(ShowLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->leads->show($lead, $request->validated('with'));

        return $this->success(new LeadResource($lead));
    }

    /**
     * POST /api/leads
     * Authorization: LeadPolicy::create (checked in StoreLeadRequest).
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leads->create($request->validated());

        return $this->success(new LeadResource($lead), 'Lead created successfully.', 201);
    }

    /**
     * PUT/PATCH /api/leads/{lead}
     * Authorization: LeadPolicy::update (checked in UpdateLeadRequest).
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->leads->update($lead, $request->validated());

        return $this->success(new LeadResource($lead), 'Lead updated successfully.');
    }

    /**
     * DELETE /api/leads/{lead} — Admin only.
     * Authorization: LeadPolicy::delete.
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorize('delete', $lead);

        $this->leads->delete($lead);

        return $this->success(null, 'Lead deleted successfully.');
    }

    /**
     * POST /api/leads/{lead}/convert — creates the Client (+ Client Portal
     * account, by default) and its first Project, and marks the lead Won.
     * If this lead was submitted by an already-onboarded client (e.g. via
     * the portal's "Start New Project" form), it just adds a Project to
     * their existing Client instead of creating a duplicate.
     * Authorization: LeadPolicy::update (checked in ConvertLeadRequest).
     */
    public function convert(ConvertLeadRequest $request, Lead $lead): JsonResponse
    {
        $result = $this->onboarding->convertLead($lead, $request->validated());

        $message = match (true) {
            $result['existing_client'] => "New project added for {$result['client']->name}.",
            $result['user'] === null => 'Client created successfully.',
            $result['email_sent'] => 'Client created successfully. Portal account created. Welcome email sent.',
            default => 'Client created successfully. Portal account created. Email could not be delivered.',
        };

        return $this->success([
            'client' => new ClientResource($result['client']),
            'project_id' => $result['project']->id,
            'existing_client' => $result['existing_client'],
            'temporary_password' => $result['temporary_password'],
            'email_sent' => $result['email_sent'],
        ], $message, 201);
    }

    // ---------- Notes ----------

    public function storeNote(StoreNoteRequest $request, Lead $lead): JsonResponse
    {
        $note = $this->leads->addNote($lead, $request->validated('body'));

        return $this->success(new NoteResource($note), 'Note added successfully.', 201);
    }

    public function updateNote(UpdateNoteRequest $request, Lead $lead, Note $note): JsonResponse
    {
        abort_if($note->noteable_id !== $lead->id || $note->noteable_type !== Lead::class, 404, 'Resource not found.');

        $note = $this->leads->updateNote($lead, $note, $request->validated('body'));

        return $this->success(new NoteResource($note), 'Note updated successfully.');
    }

    public function destroyNote(Lead $lead, Note $note): JsonResponse
    {
        $this->authorize('update', $lead);
        abort_if($note->noteable_id !== $lead->id || $note->noteable_type !== Lead::class, 404, 'Resource not found.');

        $this->leads->deleteNote($lead, $note);

        return $this->success(null, 'Note deleted successfully.');
    }

    // ---------- Reminders ----------

    public function storeReminder(StoreReminderRequest $request, Lead $lead): JsonResponse
    {
        $reminder = $this->leads->addReminder($lead, $request->validated('title'), $request->validated('due_at'));

        return $this->success(new ReminderResource($reminder), 'Reminder added successfully.', 201);
    }

    // ---------- Attachments ----------

    public function storeAttachment(StoreAttachmentRequest $request, Lead $lead): JsonResponse
    {
        $attachment = $this->leads->addAttachment($lead, $request->validated());

        return $this->success(new AttachmentResource($attachment), 'File attached successfully.', 201);
    }

    public function destroyAttachment(Lead $lead, Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $lead);
        abort_if($attachment->attachable_id !== $lead->id || $attachment->attachable_type !== Lead::class, 404, 'Resource not found.');

        $this->leads->deleteAttachment($lead, $attachment);

        return $this->success(null, 'File removed successfully.');
    }
}
