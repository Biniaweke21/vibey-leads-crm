<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Client-facing meeting view — deliberately omits `internal_notes`
 * (MeetingResource exposes it unconditionally; it's for the team only).
 */
class ClientMeetingResource extends JsonResource
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
            'duration_minutes' => $this->duration_minutes,
            'timezone' => $this->timezone,
            'location' => $this->location,
            'meeting_url' => $this->meeting_url,
            'client_response' => $this->client_response?->value,
            'client_response_label' => $this->client_response?->label(),
            'client_response_note' => $this->client_response_note,

            'organizer' => $this->whenLoaded('organizer', fn () => $this->organizer ? new UserResource($this->organizer) : null),
            'attendees' => MeetingAttendeeResource::collection($this->whenLoaded('attendees')),

            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn () => $this->project ? new ClientProjectResource($this->project) : null),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
