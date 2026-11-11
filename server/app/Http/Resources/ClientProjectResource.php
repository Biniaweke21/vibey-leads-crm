<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Client-facing project view — deliberately not ProjectResource: omits
 * internal-only relations (notes/reminders/raw activities) and exposes
 * only what a client should see about their own project. Attachments ARE
 * included — clients can upload/view their own project's files.
 */
class ClientProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'progress' => $this->progress,
            'budget' => $this->budget,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,

            'members' => UserResource::collection($this->whenLoaded('members')),
            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'design_submissions' => DesignSubmissionResource::collection($this->whenLoaded('designSubmissions')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
