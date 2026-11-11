<?php

namespace App\Http\Resources;

use App\Http\Resources\Summary\DocumentSummaryResource;
use App\Http\Resources\Summary\MeetingSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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

            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),

            // Traces back to the Lead this project was converted from, so
            // the AI Requirement Analysis stays reachable — null for
            // manually created projects.
            'lead_id' => $this->lead_id,

            // Relationships — only present when eager-loaded via ?with=
            'members' => UserResource::collection($this->whenLoaded('members')),
            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'design_submissions' => DesignSubmissionResource::collection($this->whenLoaded('designSubmissions')),
            'documents' => DocumentSummaryResource::collection($this->whenLoaded('documents')),
            'meetings' => MeetingSummaryResource::collection($this->whenLoaded('meetings')),
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
