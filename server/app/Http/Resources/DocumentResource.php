<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'title' => $this->title,
            'number' => $this->number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'version' => $this->version,
            'currency' => $this->currency,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'tax_rate' => $this->tax_rate,
            'signature_status' => $this->signature_status?->value,
            'signature_status_label' => $this->signature_status?->label(),
            'signed_at' => $this->signed_at,
            'signed_by' => $this->signed_by,
            'signer' => $this->whenLoaded('signer', fn () => $this->signer ? new UserResource($this->signer) : null),
            'include_stamp' => $this->include_stamp,
            'include_signature' => $this->include_signature,
            'body' => $this->body,

            // Always-computed totals — derived from current line items/payments,
            // so there's nothing to "recalculate" and nothing can go stale.
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discountTotal,
            'tax_total' => $this->taxTotal,
            'total' => $this->total,
            'amount_paid' => $this->amountPaid,
            'balance_due' => $this->balanceDue,

            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),
            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn () => $this->project ? new ProjectResource($this->project) : null),

            // Core to a document's identity — always shown (DocumentService
            // always eager-loads these to compute the totals above), unlike
            // the genuinely-optional relations below which are ?with=-gated.
            'line_items' => DocumentLineItemResource::collection($this->lineItems),
            'payments' => DocumentPaymentResource::collection($this->payments),

            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? new UserResource($this->creator) : null),

            // Relationships — only present when eager-loaded via ?with=
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
