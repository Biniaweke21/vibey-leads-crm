<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\IndexDocumentRequest;
use App\Http\Requests\Portal\IndexMeetingRequest;
use App\Http\Requests\Portal\IndexProjectRequest;
use App\Http\Requests\Portal\RespondToMeetingRequest;
use App\Http\Requests\Portal\ReviewDesignSubmissionRequest;
use App\Http\Requests\Portal\StoreMeetingRequest;
use App\Http\Requests\Portal\StorePaymentProofRequest;
use App\Http\Requests\Portal\StoreProjectAttachmentRequest;
use App\Http\Requests\Portal\TimelineRequest;
use App\Http\Requests\Portal\UpdateProfileRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ClientDashboardResource;
use App\Http\Resources\ClientDocumentResource;
use App\Http\Resources\ClientMeetingResource;
use App\Http\Resources\ClientProfileResource;
use App\Http\Resources\ClientProjectResource;
use App\Http\Resources\ClientTimelineResource;
use App\Http\Resources\DesignSubmissionResource;
use App\Http\Resources\DocumentPaymentResource;
use App\Models\DesignSubmission;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Setting;
use App\Services\ClientPortalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only except updateProfile(), storeProjectAttachment(), and
 * storeMeeting(). No Policy — the `role:client` route middleware gates
 * entry, and ClientPortalService::show*() aborts with 404 (not 403)
 * whenever the resolved model doesn't belong to the authenticated user's
 * own client_id, per the IDOR requirement.
 */
class ClientPortalController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function dashboard(Request $request): JsonResponse
    {
        return $this->success(new ClientDashboardResource($this->portal->dashboard($request->user())));
    }

    public function projects(IndexProjectRequest $request): JsonResponse
    {
        $paginator = $this->portal->projects($request->user(), $request->validated());
        $data = ClientProjectResource::collection($paginator->getCollection())->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    public function showProject(Request $request, Project $project): JsonResponse
    {
        return $this->success(new ClientProjectResource($this->portal->showProject($request->user(), $project)));
    }

    public function storeProjectAttachment(StoreProjectAttachmentRequest $request, Project $project): JsonResponse
    {
        $attachment = $this->portal->addProjectAttachment($request->user(), $project, $request->validated());

        return $this->success(new AttachmentResource($attachment), 'File attached successfully.', 201);
    }

    public function reviewDesign(ReviewDesignSubmissionRequest $request, Project $project, DesignSubmission $designSubmission): JsonResponse
    {
        $design = $this->portal->reviewDesign($request->user(), $project, $designSubmission, $request->validated());

        return $this->success(new DesignSubmissionResource($design), 'Design '.$design->status->value.'.');
    }

    public function documents(IndexDocumentRequest $request): JsonResponse
    {
        $paginator = $this->portal->documents($request->user(), $request->validated());
        $data = ClientDocumentResource::collection($paginator->getCollection())->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    public function showDocument(Request $request, Document $document): JsonResponse
    {
        return $this->success(new ClientDocumentResource($this->portal->showDocument($request->user(), $document)));
    }

    public function approveDocument(Request $request, Document $document): JsonResponse
    {
        $document = $this->portal->approveDocument($request->user(), $document);

        return $this->success(new ClientDocumentResource($document), 'Proposal approved.');
    }

    public function rejectDocument(Request $request, Document $document): JsonResponse
    {
        $document = $this->portal->rejectDocument($request->user(), $document);

        return $this->success(new ClientDocumentResource($document), 'Proposal rejected.');
    }

    /** GET /api/portal/documents/{document}/pdf — real PDF, not the JSON envelope. IDOR-checked via showDocument(). */
    public function downloadDocumentPdf(Request $request, Document $document): Response
    {
        $document = $this->portal->showDocument($request->user(), $document);
        $document->load(['client', 'project']);

        $pdf = Pdf::loadView('documents.pdf', [
            'document' => $document,
            'company' => Setting::get('company', []),
        ]);

        $filename = ($document->number ?? $document->type->value).'-'.$document->id.'.pdf';

        return $pdf->download($filename);
    }

    public function storePaymentProof(StorePaymentProofRequest $request, Document $document): JsonResponse
    {
        $payment = $this->portal->submitPaymentProof($request->user(), $document, $request->validated());

        return $this->success(new DocumentPaymentResource($payment), 'Payment proof submitted — awaiting approval.', 201);
    }

    public function meetings(IndexMeetingRequest $request): JsonResponse
    {
        $paginator = $this->portal->meetings($request->user(), $request->validated());
        $data = ClientMeetingResource::collection($paginator->getCollection())->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    public function showMeeting(Request $request, Meeting $meeting): JsonResponse
    {
        return $this->success(new ClientMeetingResource($this->portal->showMeeting($request->user(), $meeting)));
    }

    public function respondMeeting(RespondToMeetingRequest $request, Meeting $meeting): JsonResponse
    {
        $meeting = $this->portal->respondToMeeting($request->user(), $meeting, $request->validated());

        return $this->success(new ClientMeetingResource($meeting), 'Response recorded.');
    }

    public function storeMeeting(StoreMeetingRequest $request): JsonResponse
    {
        $meeting = $this->portal->requestMeeting($request->user(), $request->validated());

        return $this->success(new ClientMeetingResource($meeting->load(['attendees.user', 'organizer', 'project'])), 'Meeting booked successfully.', 201);
    }

    public function timeline(TimelineRequest $request): JsonResponse
    {
        return $this->success(ClientTimelineResource::collection($this->portal->timeline($request->user(), $request->validated())));
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success(new ClientProfileResource($this->portal->profile($request->user())));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $this->portal->updateProfile($request->user(), $request->validated());

        return $this->success(new ClientProfileResource($profile), 'Profile updated successfully.');
    }
}
