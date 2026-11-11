<?php

namespace App\Http\Resources\Summary;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal placeholder — the Documents module isn't built yet. Exposes just
 * enough for a Client's `?with=documents` to render a list. Replace with the
 * real DocumentResource when that module is implemented.
 */
class DocumentSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'number' => $this->number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
