<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\IndexDocumentRequest;
use App\Http\Requests\Document\ShowDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\StoreLineItemRequest;
use App\Http\Requests\Document\StorePaymentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Requests\Document\UpdateLineItemRequest;
use App\Http\Requests\Document\UpdatePaymentRequest;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\DocumentLineItemResource;
use App\Http\Resources\DocumentPaymentResource;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentLineItem;
use App\Models\DocumentPayment;
use App\Models\Setting;
use App\Services\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    /**
     * GET /api/documents — search, filter, sort, paginate.
     * Authorization: DocumentPolicy::viewAny (checked in IndexDocumentRequest).
     */
    public function index(IndexDocumentRequest $request): JsonResponse
    {
        $paginator = $this->documents->list($request->validated());

        $data = (new DocumentCollection($paginator->getCollection()))->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    /**
     * GET /api/documents/{document}
     * Authorization: DocumentPolicy::view (checked in ShowDocumentRequest).
     */
    public function show(ShowDocumentRequest $request, Document $document): JsonResponse
    {
        $document = $this->documents->show($document, $request->validated('with'));

        return $this->success(new DocumentResource($document));
    }

    /**
     * POST /api/documents
     * Authorization: DocumentPolicy::create (checked in StoreDocumentRequest).
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documents->create($request->validated());

        return $this->success(new DocumentResource($document), 'Document created successfully.', 201);
    }

    /**
     * PUT/PATCH /api/documents/{document}
     * Authorization: DocumentPolicy::update (checked in UpdateDocumentRequest).
     */
    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $document = $this->documents->update($document, $request->validated());

        return $this->success(new DocumentResource($document), 'Document updated successfully.');
    }

    /**
     * DELETE /api/documents/{document} — Admin only.
     * Authorization: DocumentPolicy::delete.
     */
    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->documents->delete($document);

        return $this->success(null, 'Document deleted successfully.');
    }

    // ---------- Line items ----------

    public function storeLineItem(StoreLineItemRequest $request, Document $document): JsonResponse
    {
        $lineItem = $this->documents->addLineItem($document, $request->validated());

        return $this->success(new DocumentLineItemResource($lineItem), 'Line item added successfully.', 201);
    }

    public function updateLineItem(UpdateLineItemRequest $request, Document $document, DocumentLineItem $lineItem): JsonResponse
    {
        abort_if($lineItem->document_id !== $document->id, 404, 'Resource not found.');

        $lineItem = $this->documents->updateLineItem($document, $lineItem, $request->validated());

        return $this->success(new DocumentLineItemResource($lineItem), 'Line item updated successfully.');
    }

    public function destroyLineItem(Document $document, DocumentLineItem $lineItem): JsonResponse
    {
        $this->authorize('update', $document);
        abort_if($lineItem->document_id !== $document->id, 404, 'Resource not found.');

        $this->documents->deleteLineItem($document, $lineItem);

        return $this->success(null, 'Line item deleted successfully.');
    }

    // ---------- Payments ----------

    public function storePayment(StorePaymentRequest $request, Document $document): JsonResponse
    {
        $payment = $this->documents->addPayment($document, $request->validated());

        return $this->success(new DocumentPaymentResource($payment), 'Payment recorded successfully.', 201);
    }

    public function updatePayment(UpdatePaymentRequest $request, Document $document, DocumentPayment $payment): JsonResponse
    {
        abort_if($payment->document_id !== $document->id, 404, 'Resource not found.');

        $payment = $this->documents->updatePayment($document, $payment, $request->validated());

        return $this->success(new DocumentPaymentResource($payment), 'Payment updated successfully.');
    }

    public function destroyPayment(Document $document, DocumentPayment $payment): JsonResponse
    {
        $this->authorize('update', $document);
        abort_if($payment->document_id !== $document->id, 404, 'Resource not found.');

        $this->documents->deletePayment($document, $payment);

        return $this->success(null, 'Payment deleted successfully.');
    }

    // ---------- Proposal workflow ----------

    public function duplicate(Document $document): JsonResponse
    {
        $this->authorize('create', Document::class);

        $copy = $this->documents->duplicate($document);

        return $this->success(new DocumentResource($copy), 'Proposal duplicated successfully.', 201);
    }

    public function approve(Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        return $this->success(new DocumentResource($this->documents->approve($document)), 'Proposal approved successfully.');
    }

    public function reject(Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        return $this->success(new DocumentResource($this->documents->reject($document)), 'Proposal rejected successfully.');
    }

    public function convertToContract(Document $document): JsonResponse
    {
        $this->authorize('create', Document::class);

        $contract = $this->documents->convertToContract($document);

        return $this->success(new DocumentResource($contract), 'Contract created from proposal.', 201);
    }

    // ---------- Send (any type) ----------

    public function send(Request $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $options = $request->validate([
            'include_stamp' => ['sometimes', 'boolean'],
            'include_signature' => ['sometimes', 'boolean'],
        ]);

        return $this->success(new DocumentResource($this->documents->send($document, $options)), 'Document sent successfully.');
    }

    public function sendReminder(Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $this->documents->sendReminder($document);

        return $this->success(null, 'Reminder sent successfully.');
    }

    // ---------- Contract workflow ----------

    public function sign(Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        return $this->success(new DocumentResource($this->documents->sign($document)), 'Contract signed successfully.');
    }

    public function expire(Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        return $this->success(new DocumentResource($this->documents->expire($document)), 'Contract expired successfully.');
    }

    public function convertToInvoice(Document $document): JsonResponse
    {
        $this->authorize('create', Document::class);

        $invoice = $this->documents->convertToInvoice($document);

        return $this->success(new DocumentResource($invoice), 'Invoice created from contract.', 201);
    }

    /** GET /api/documents/{document}/pdf — real PDF, not the JSON envelope. */
    public function downloadPdf(Document $document): Response
    {
        $this->authorize('view', $document);

        $document->load(['lineItems', 'payments', 'client', 'project', 'signer']);

        $pdf = Pdf::loadView('documents.pdf', [
            'document' => $document,
            'company' => Setting::get('company', []),
        ]);

        $filename = ($document->number ?? $document->type->value).'-'.$document->id.'.pdf';

        return $pdf->download($filename);
    }
}
