<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\SignatureStatus;
use App\Mail\DocumentSentMail;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Document;
use App\Models\DocumentLineItem;
use App\Models\DocumentPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    private const ALLOWED_SORTS = ['created_at', 'updated_at', 'total_amount', 'status', 'client_name', 'number'];

    /** These are ALWAYS eager-loaded (see list()/show()) — core to a document's identity, not opt-in. */
    private const ALWAYS_LOAD = ['lineItems', 'payments.attachments'];

    /** ponytail: fixed net-14 terms, promote to a Setting if clients ever need per-contract terms. */
    private const INVOICE_DUE_DAYS = 14;

    /** Requested include name => actual (possibly nested) eager-load path. */
    private const INCLUDE_MAP = [
        'client' => 'client',
        'project' => 'project',
        'lineItems' => 'lineItems',
        'payments' => 'payments',
        'notes' => 'notes.user',
        'activities' => 'activities.user',
        'attachments' => 'attachments.uploader',
        'reminders' => 'reminders.user',
    ];

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Document::query()->with(self::ALWAYS_LOAD);
        $this->applySortableSubqueries($query);

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyInList($query, 'type', $filters['type'] ?? null);
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyInList($query, 'client_id', $filters['client_id'] ?? null, 'intval');
        $this->applyInList($query, 'project_id', $filters['project_id'] ?? null, 'intval');
        $this->applyAmountRange($query, $filters['amount_min'] ?? null, $filters['amount_max'] ?? null);
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

    public function show(Document $document, ?string $with = null): Document
    {
        $document->load(self::ALWAYS_LOAD);

        if ($with) {
            $document->load($this->resolveIncludes($with));
        }

        // Lazily surface a calendar-driven Overdue transition even if nobody
        // has recorded a payment recently (payment-driven recalculation
        // happens eagerly in addPayment/updatePayment/deletePayment/line
        // item mutations — this covers the "time alone" case on read).
        $this->recalculateInvoiceStatus($document);

        return $document;
    }

    public function create(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = DocumentStatus::Draft->value;
            $data['version'] = 1;
            $data['created_by'] = auth()->id();

            $document = Document::create($data);

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "{$document->type->label()} created",
            ]);

            return $document->load(self::ALWAYS_LOAD);
        });
    }

    public function update(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data) {
            $document->fill($data);
            $document->save();

            return $document->load(self::ALWAYS_LOAD);
        });
    }

    /**
     * Soft delete only — client/project keep their FK intact. The DB-level
     * `cascadeOnDelete()` on those tables only fires on a real SQL DELETE,
     * which SoftDeletes never issues (it's an UPDATE).
     */
    public function delete(Document $document): void
    {
        $document->delete();
    }

    // ---------- Line items ----------

    public function addLineItem(Document $document, array $data): DocumentLineItem
    {
        return DB::transaction(function () use ($document, $data) {
            $data['tax_rate'] ??= (float) $document->tax_rate;

            $lineItem = $document->lineItems()->create($data);

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Line item added: {$lineItem->description}",
            ]);

            $this->recalculateInvoiceStatus($document->refresh()->load('lineItems', 'payments'));

            return $lineItem;
        });
    }

    public function updateLineItem(Document $document, DocumentLineItem $lineItem, array $data): DocumentLineItem
    {
        return DB::transaction(function () use ($document, $lineItem, $data) {
            $lineItem->fill($data);
            $lineItem->save();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Line item updated: {$lineItem->description}",
            ]);

            $this->recalculateInvoiceStatus($document->refresh()->load('lineItems', 'payments'));

            return $lineItem;
        });
    }

    public function deleteLineItem(Document $document, DocumentLineItem $lineItem): void
    {
        DB::transaction(function () use ($document, $lineItem) {
            $description = $lineItem->description;
            $lineItem->delete();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Line item removed: {$description}",
            ]);

            $this->recalculateInvoiceStatus($document->refresh()->load('lineItems', 'payments'));
        });
    }

    // ---------- Payments ----------

    public function addPayment(Document $document, array $data): DocumentPayment
    {
        return DB::transaction(function () use ($document, $data) {
            $data['status'] ??= PaymentStatus::Paid->value;
            // Revenue reporting (AnalyticsService) filters paid payments by
            // paid_at — a payment recorded as Paid with no explicit date
            // must default to now(), or it silently never counts as revenue.
            if ($data['status'] === PaymentStatus::Paid->value) {
                $data['paid_at'] ??= now();
            }

            $payment = $document->payments()->create($data);

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Payment recorded: {$payment->amount} {$document->currency}".($payment->method ? " via {$payment->method}" : ''),
            ]);

            $this->recalculateInvoiceStatus($document->refresh()->load('lineItems', 'payments'));

            return $payment;
        });
    }

    public function updatePayment(Document $document, DocumentPayment $payment, array $data): DocumentPayment
    {
        return DB::transaction(function () use ($document, $payment, $data) {
            $payment->fill($data);
            if ($payment->status === PaymentStatus::Paid && ! $payment->paid_at) {
                $payment->paid_at = now();
            }
            $payment->save();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Payment updated: {$payment->amount} {$document->currency}",
            ]);

            $this->recalculateInvoiceStatus($document->refresh()->load('lineItems', 'payments'));

            return $payment;
        });
    }

    public function deletePayment(Document $document, DocumentPayment $payment): void
    {
        DB::transaction(function () use ($document, $payment) {
            $amount = $payment->amount;
            $payment->delete();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Payment removed: {$amount} {$document->currency}",
            ]);

            $this->recalculateInvoiceStatus($document->refresh()->load('lineItems', 'payments'));
        });
    }

    // ---------- Proposal workflow ----------

    public function duplicate(Document $document): Document
    {
        $this->ensureType($document, DocumentType::Proposal, 'duplicated');

        return DB::transaction(function () use ($document) {
            $copy = Document::create([
                'type' => $document->type,
                'client_id' => $document->client_id,
                'project_id' => $document->project_id,
                'title' => "{$document->title} (Copy)",
                'status' => DocumentStatus::Draft,
                'version' => 1,
                'currency' => $document->currency,
                'tax_rate' => $document->tax_rate,
                'body' => $document->body,
                'created_by' => auth()->id(),
            ]);

            foreach ($document->lineItems as $item) {
                $copy->lineItems()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'discount' => $item->discount,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $copy->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Duplicated from proposal #{$document->id}",
            ]);
            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Duplicated as proposal #{$copy->id}",
            ]);

            return $copy->load(self::ALWAYS_LOAD);
        });
    }

    public function approve(Document $document): Document
    {
        $this->ensureType($document, DocumentType::Proposal, 'approved');

        return $this->transitionStatus($document, DocumentStatus::Approved);
    }

    /**
     * Turns an approved Proposal into a Contract — carries over the client,
     * project, pricing, and deliverables, and starts the contract term
     * today. The proposal itself is untouched; this creates a new Document.
     */
    public function convertToContract(Document $proposal): Document
    {
        $this->ensureType($proposal, DocumentType::Proposal, 'converted to a contract');

        if ($proposal->status !== DocumentStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => ['Only an approved proposal can be converted to a contract.'],
            ]);
        }

        return DB::transaction(function () use ($proposal) {
            $body = $proposal->body ?? [];
            $paymentTerms = collect($body['payment_terms'] ?? [])
                ->map(fn ($t) => "{$t['percentage']}% — {$t['label']}")
                ->implode(', ');

            $contract = Document::create([
                'type' => DocumentType::Contract->value,
                'client_id' => $proposal->client_id,
                'project_id' => $proposal->project_id,
                'title' => "Contract for {$proposal->title}",
                'status' => DocumentStatus::Draft,
                'version' => 1,
                'currency' => $proposal->currency,
                'tax_rate' => $proposal->tax_rate,
                'issue_date' => now()->toDateString(),
                'body' => [
                    'contract_terms' => trim(($body['scope_of_work'] ?? '')."\n\nPayment terms: {$paymentTerms}."),
                    'deliverables' => $body['deliverables'] ?? [],
                    'start_date' => now()->toDateString(),
                    'end_date' => null,
                ],
                'created_by' => auth()->id(),
            ]);

            foreach ($proposal->lineItems as $item) {
                $contract->lineItems()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'discount' => $item->discount,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $contract->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Created from proposal #{$proposal->id}",
            ]);
            $proposal->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Converted to contract #{$contract->id}",
            ]);

            return $contract->load(self::ALWAYS_LOAD);
        });
    }

    public function reject(Document $document): Document
    {
        $this->ensureType($document, DocumentType::Proposal, 'rejected');

        return $this->transitionStatus($document, DocumentStatus::Rejected);
    }

    /**
     * Turns a Signed Contract into an Invoice — carries over the client,
     * project, pricing, and line items so the invoice amount is always
     * calculated from what was actually contracted, never re-typed by
     * hand. The contract itself is untouched; this creates a new Document.
     */
    public function convertToInvoice(Document $contract): Document
    {
        $this->ensureType($contract, DocumentType::Contract, 'converted to an invoice');

        if ($contract->status !== DocumentStatus::Signed) {
            throw ValidationException::withMessages([
                'status' => ['Only a signed contract can be converted to an invoice.'],
            ]);
        }

        return DB::transaction(function () use ($contract) {
            $invoice = Document::create([
                'type' => DocumentType::Invoice->value,
                'client_id' => $contract->client_id,
                'project_id' => $contract->project_id,
                'title' => "Invoice for {$contract->title}",
                'status' => DocumentStatus::Draft,
                'version' => 1,
                'currency' => $contract->currency,
                'tax_rate' => $contract->tax_rate,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(self::INVOICE_DUE_DAYS)->toDateString(),
                'body' => [
                    'notes' => "Billed per contract #{$contract->id}: {$contract->title}.",
                ],
                'created_by' => auth()->id(),
            ]);

            foreach ($contract->lineItems as $item) {
                $invoice->lineItems()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'discount' => $item->discount,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $invoice->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Created from contract #{$contract->id}",
            ]);
            $contract->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Converted to invoice #{$invoice->id}",
            ]);

            return $invoice->load(self::ALWAYS_LOAD);
        });
    }

    // ---------- Send (any type) ----------

    /**
     * Transitions the document to Sent and emails the client's primary
     * contact a copy with the PDF attached. Uses the app's configured mail
     * driver (MAIL_MAILER in .env — "log" by default, so nothing is
     * actually delivered until real SMTP credentials are set).
     *
     * $options: 'include_stamp' / 'include_signature' — the sender's choice
     * of whether the company stamp and their own signature appear on the
     * PDF. Reuses signed_by (already on every document type, not just
     * contracts) to record whose signature that is; a later Contract::sign()
     * call overwrites it with the actual counter-signer, which is correct.
     */
    public function send(Document $document, array $options = []): Document
    {
        $document = DB::transaction(function () use ($document, $options) {
            $document->include_stamp = $options['include_stamp'] ?? $document->include_stamp;
            $document->include_signature = $options['include_signature'] ?? $document->include_signature;
            $document->signed_by = auth()->id();

            return $this->transitionStatus($document, DocumentStatus::Sent);
        });

        $contact = $this->resolvePrimaryContact($document->client);

        if ($contact) {
            $this->sendMailBestEffort(
                fn () => Mail::to($contact->email)->send(new DocumentSentMail($document->load(['client', 'project', 'lineItems', 'signer']))),
                $document,
                "{$document->type->label()} emailed to {$contact->email}",
                "Could not email the {$document->type->value} to {$contact->email}",
            );
        }

        return $document;
    }

    /**
     * Emails the client's primary contact a payment reminder for an
     * outstanding invoice.
     */
    public function sendReminder(Document $document): void
    {
        $this->ensureType($document, DocumentType::Invoice, 'sent a reminder for');

        $contact = $this->resolvePrimaryContact($document->client);

        if (! $contact) {
            throw ValidationException::withMessages([
                'client' => ['This client has no contact email on file to send a reminder to.'],
            ]);
        }

        Mail::to($contact->email)->send(new InvoiceReminderMail($document->load(['client', 'payments'])));

        $document->activities()->create([
            'user_id' => auth()->id(),
            'description' => "Payment reminder sent to {$contact->email}",
        ]);
    }

    /** The client's primary contact, falling back to their first contact — null if neither has an email. */
    private function resolvePrimaryContact(Client $client): ?ClientContact
    {
        $contact = $client->contacts()->where('is_primary', true)->first()
            ?? $client->contacts()->first();

        return $contact && $contact->email ? $contact : null;
    }

    /**
     * Runs a mail dispatch without letting delivery failures (e.g. bad SMTP
     * credentials) fail the surrounding request — the document/status
     * change this is attached to has already been persisted by this point.
     */
    private function sendMailBestEffort(callable $dispatch, Document $document, string $successMessage, string $failureMessage): void
    {
        try {
            $dispatch();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => $successMessage,
            ]);
        } catch (\Throwable $e) {
            Log::warning("{$failureMessage}: {$e->getMessage()}");

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "{$failureMessage} (mail delivery failed)",
            ]);
        }
    }

    // ---------- Contract workflow ----------

    public function sign(Document $document): Document
    {
        $this->ensureType($document, DocumentType::Contract, 'signed');

        return DB::transaction(function () use ($document) {
            $document->status = DocumentStatus::Signed;
            $document->signature_status = SignatureStatus::FullySigned;
            $document->signed_at = now();
            $document->signed_by = auth()->id();
            $document->save();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Contract signed',
            ]);

            return $document->load(self::ALWAYS_LOAD);
        });
    }

    public function expire(Document $document): Document
    {
        $this->ensureType($document, DocumentType::Contract, 'expired');

        return $this->transitionStatus($document, DocumentStatus::Expired);
    }

    private function transitionStatus(Document $document, DocumentStatus $status): Document
    {
        return DB::transaction(function () use ($document, $status) {
            $document->status = $status;
            $document->save();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "{$document->type->label()} {$status->label()}",
            ]);

            return $document->load(self::ALWAYS_LOAD);
        });
    }

    private function ensureType(Document $document, DocumentType $expected, string $action): void
    {
        if ($document->type !== $expected) {
            throw ValidationException::withMessages([
                'type' => ["Only {$expected->label()}s can be {$action}."],
            ]);
        }
    }

    /**
     * Auto-derive an invoice's status from its recorded payments vs. grand
     * total, and from its due date. Never touches Draft (payment tracking
     * only begins once an invoice is sent), and never touches other
     * document types.
     */
    private function recalculateInvoiceStatus(Document $document): void
    {
        if ($document->type !== DocumentType::Invoice || $document->status === DocumentStatus::Draft) {
            return;
        }

        $grandTotal = $document->total;
        $amountPaid = $document->amountPaid;

        $newStatus = match (true) {
            $grandTotal > 0 && $amountPaid >= $grandTotal => DocumentStatus::Paid,
            $amountPaid > 0 => DocumentStatus::PartiallyPaid,
            $document->due_date && $document->due_date->isPast() => DocumentStatus::Overdue,
            default => DocumentStatus::Sent,
        };

        if ($document->status !== $newStatus) {
            $document->status = $newStatus;
            $document->saveQuietly();

            $document->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Invoice status automatically changed to {$newStatus->label()}",
            ]);
        }
    }

    private function applySortableSubqueries(Builder $query): void
    {
        $query->addSelect([
            'client_name' => Client::select('name')->whereColumn('id', 'documents.client_id'),
            'total_amount' => DocumentLineItem::selectRaw('COALESCE(SUM((quantity * unit_price - discount) * (1 + tax_rate / 100)), 0)')
                ->whereColumn('document_id', 'documents.id'),
        ]);
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        if (empty($term)) {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('number', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhereHas('client', fn (Builder $cq) => $cq->where('name', 'like', $like))
                ->orWhereHas('project', fn (Builder $pq) => $pq->where('name', 'like', $like));
        });
    }

    private function applyAmountRange(Builder $query, null|string|float $min, null|string|float $max): void
    {
        if ($min !== null) {
            $query->havingRaw('total_amount >= ?', [(float) $min]);
        }
        if ($max !== null) {
            $query->havingRaw('total_amount <= ?', [(float) $max]);
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
