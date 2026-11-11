<?php

namespace App\Http\Resources\Summary;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal placeholder — the Projects module isn't built yet. Exposes just
 * enough for a Client's `?with=projects` to render a list. Replace with the
 * real ProjectResource when that module is implemented.
 */
class ProjectSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'progress' => $this->progress,
            'budget' => $this->budget,
        ];
    }
}
