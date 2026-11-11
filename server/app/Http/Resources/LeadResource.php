<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Contact
            'full_name' => $this->full_name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'city' => $this->city,
            'preferred_contact' => $this->preferred_contact,

            // Business
            'business_name' => $this->business_name,
            'industry' => $this->industry,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'business_description' => $this->business_description,

            // Project scope
            'project_idea' => $this->project_idea,
            'main_goal' => $this->main_goal,
            'target_users' => $this->target_users,
            'problems' => $this->problems,
            'core_features' => $this->core_features,

            // Multi-value intake
            'services' => $this->services,
            'other_service' => $this->other_service,
            'features' => $this->features,
            'other_feature' => $this->other_feature,
            'design_style' => $this->design_style,
            'reference_links' => $this->reference_links,

            'budget' => $this->budget,
            'timeline' => $this->timeline,
            'additional_notes' => $this->additional_notes,

            // Pipeline
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'ai_analysis_status' => $this->ai_analysis_status->value,
            'ai_analysis' => $this->ai_analysis,
            'stage_entered_at' => $this->stage_entered_at,
            'due_date' => $this->due_date,

            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? new UserResource($this->assignee) : null),
            'converted_client_id' => $this->converted_client_id,
            // The project this lead became once converted/won — lets the frontend
            // scope a "find this lead's proposal" lookup to the right project
            // instead of just the client (who may have several).
            'project_id' => $this->project?->id,

            // Lets the frontend show accurate "Convert" copy up front — if this
            // lead's email already belongs to a client-portal account (e.g. it
            // came from that client's own "Start New Project" form), converting
            // will add a Project to their existing Client, not create a new one.
            'existing_client_match' => $this->existingClientMatch(),

            // Relationships — only present when eager-loaded via ?with=
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** @return array{id: int, name: string}|null */
    private function existingClientMatch(): ?array
    {
        if ($this->converted_client_id) {
            return null; // already converted — no longer relevant
        }

        $user = User::where('email', $this->email)->where('role', UserRole::Client)->first();

        return $user?->client ? ['id' => $user->client->id, 'name' => $user->client->name] : null;
    }
}
