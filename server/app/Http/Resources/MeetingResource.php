<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            // Always derived from starts_at/ends_at — never stale.
            'duration_minutes' => $this->duration_minutes,
            'timezone' => $this->timezone,
            'location' => $this->location,
            'meeting_url' => $this->meeting_url,
            'internal_notes' => $this->internal_notes,
            'client_response' => $this->client_response?->value,
            'client_response_label' => $this->client_response?->label(),
            'client_response_note' => $this->client_response_note,
            'client_responded_at' => $this->client_responded_at,

            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => $this->client ? new ClientResource($this->client) : null),
            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn () => $this->project ? new ProjectResource($this->project) : null),
            'lead_id' => $this->lead_id,
            'lead' => $this->whenLoaded('lead', fn () => $this->lead ? new LeadResource($this->lead) : null),

            'organizer_id' => $this->organizer_id,
            'organizer' => $this->whenLoaded('organizer', fn () => $this->organizer ? new UserResource($this->organizer) : null),

            // Relationships — only present when eager-loaded via ?with=
            'attendees' => MeetingAttendeeResource::collection($this->whenLoaded('attendees')),
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
