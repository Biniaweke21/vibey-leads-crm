<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name, // Title
            'description' => $this->description,
            'version' => $this->version,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            'url' => $this->url, // Attachment reference
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'note' => $this->note,
            'submitted_by' => $this->submitted_by,
            'submitter' => $this->whenLoaded('submitter', fn () => $this->submitter ? new UserResource($this->submitter) : null),
            'submitted_at' => $this->submitted_at,
            'decided_by' => $this->decided_by,
            'decider' => $this->whenLoaded('decider', fn () => $this->decider ? new UserResource($this->decider) : null),
            'decided_at' => $this->decided_at,
            'created_at' => $this->created_at,
        ];
    }
}
