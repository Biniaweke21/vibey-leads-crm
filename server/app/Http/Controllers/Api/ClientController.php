<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\IndexClientRequest;
use App\Http\Requests\Client\ShowClientRequest;
use App\Http\Requests\Client\StoreAttachmentRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\StoreContactRequest;
use App\Http\Requests\Client\StoreNoteRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Requests\Client\UpdateContactRequest;
use App\Http\Requests\Client\UpdateNoteRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\UserResource;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Note;
use App\Services\ClientOnboardingService;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clients,
        private readonly ClientOnboardingService $onboarding,
    ) {}

    /**
     * GET /api/clients — search, filter, sort, paginate.
     * Authorization: ClientPolicy::viewAny (checked in IndexClientRequest).
     */
    public function index(IndexClientRequest $request): JsonResponse
    {
        $paginator = $this->clients->list($request->validated());

        $data = (new ClientCollection($paginator->getCollection()))->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    /**
     * GET /api/clients/{client}
     * Authorization: ClientPolicy::view (checked in ShowClientRequest).
     */
    public function show(ShowClientRequest $request, Client $client): JsonResponse
    {
        $client = $this->clients->show($client, $request->validated('with'));

        return $this->success(new ClientResource($client));
    }

    /**
     * POST /api/clients
     * Authorization: ClientPolicy::create (checked in StoreClientRequest).
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clients->create($request->validated());

        return $this->success(new ClientResource($client), 'Client created successfully.', 201);
    }

    /**
     * PUT/PATCH /api/clients/{client}
     * Authorization: ClientPolicy::update (checked in UpdateClientRequest).
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client = $this->clients->update($client, $request->validated());

        return $this->success(new ClientResource($client), 'Client updated successfully.');
    }

    /**
     * DELETE /api/clients/{client} — Admin only.
     * Authorization: ClientPolicy::delete.
     */
    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        $this->clients->delete($client);

        return $this->success(null, 'Client deleted successfully.');
    }

    /**
     * POST /api/clients/{client}/contacts
     * Authorization: ClientPolicy::update (checked in StoreContactRequest).
     */
    public function storeContact(StoreContactRequest $request, Client $client): JsonResponse
    {
        $contact = $this->clients->addContact($client, $request->validated());

        return $this->success(new ContactResource($contact), 'Contact added successfully.', 201);
    }

    /**
     * PUT/PATCH /api/clients/{client}/contacts/{contact}
     * Authorization: ClientPolicy::update (checked in UpdateContactRequest).
     */
    public function updateContact(UpdateContactRequest $request, Client $client, ClientContact $contact): JsonResponse
    {
        abort_if($contact->client_id !== $client->id, 404, 'Resource not found.');

        $contact = $this->clients->updateContact($client, $contact, $request->validated());

        return $this->success(new ContactResource($contact), 'Contact updated successfully.');
    }

    // ---------- Portal Account (Admin only — gated by the route middleware) ----------

    public function enablePortal(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $result = $this->onboarding->enablePortal($client);

        return $this->success($this->portalPayload($result), 'Portal account enabled.');
    }

    public function disablePortal(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $this->onboarding->disablePortal($client);

        return $this->success(null, 'Portal account disabled.');
    }

    public function resetPortalPassword(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $result = $this->onboarding->resetPassword($client);

        return $this->success($this->portalPayload($result), 'Password reset.');
    }

    public function regeneratePortalPassword(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $result = $this->onboarding->regeneratePassword($client);

        return $this->success($this->portalPayload($result), 'Temporary password regenerated.');
    }

    public function resendWelcomeEmail(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $result = $this->onboarding->resendWelcomeEmail($client);

        $message = $result['email_sent'] ? 'Welcome email sent.' : 'Email could not be delivered.';

        return $this->success($this->portalPayload($result), $message);
    }

    /** @return array<string, mixed> */
    private function portalPayload(array $result): array
    {
        return [
            'user' => new UserResource($result['user']),
            'temporary_password' => $result['temporary_password'],
            'email_sent' => $result['email_sent'],
        ];
    }

    // ---------- Notes ----------

    public function storeNote(StoreNoteRequest $request, Client $client): JsonResponse
    {
        $note = $this->clients->addNote($client, $request->validated('body'));

        return $this->success(new NoteResource($note), 'Note added successfully.', 201);
    }

    public function updateNote(UpdateNoteRequest $request, Client $client, Note $note): JsonResponse
    {
        abort_if($note->noteable_id !== $client->id || $note->noteable_type !== Client::class, 404, 'Resource not found.');

        $note = $this->clients->updateNote($client, $note, $request->validated('body'));

        return $this->success(new NoteResource($note), 'Note updated successfully.');
    }

    public function destroyNote(Client $client, Note $note): JsonResponse
    {
        $this->authorize('update', $client);
        abort_if($note->noteable_id !== $client->id || $note->noteable_type !== Client::class, 404, 'Resource not found.');

        $this->clients->deleteNote($client, $note);

        return $this->success(null, 'Note deleted successfully.');
    }

    // ---------- Attachments ----------

    public function storeAttachment(StoreAttachmentRequest $request, Client $client): JsonResponse
    {
        $attachment = $this->clients->addAttachment($client, $request->validated());

        return $this->success(new AttachmentResource($attachment), 'File attached successfully.', 201);
    }

    public function destroyAttachment(Client $client, Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $client);
        abort_if($attachment->attachable_id !== $client->id || $attachment->attachable_type !== Client::class, 404, 'Resource not found.');

        $this->clients->deleteAttachment($client, $attachment);

        return $this->success(null, 'File removed successfully.');
    }
}
