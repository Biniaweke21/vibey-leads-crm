<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Client-facing document view — omits internal fields (created_by, signer,
 * notes/activities/attachments) that DocumentResource exposes for the team.
 */
class ClientDocumentResource extends JsonResource
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
            'currency' => $this->currency,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'signature_status' => $this->signature_status?->value,
            'signature_status_label' => $this->signature_status?->label(),
            'body' => $this->body,

            // Always-computed from current line items/payments — never stale.
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discountTotal,
            'tax_total' => $this->taxTotal,
            'total' => $this->total,
            'amount_paid' => $this->amountPaid,
            'balance_due' => $this->balanceDue,

            'line_items' => DocumentLineItemResource::collection($this->lineItems),
            'payments' => DocumentPaymentResource::collection($this->payments),

            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn () => $this->project ? new ClientProjectResource($this->project) : null),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
