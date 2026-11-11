<?php

namespace App\Http\Resources;

use App\Http\Resources\Summary\DocumentSummaryResource;
use App\Http\Resources\Summary\MeetingSummaryResource;
use App\Http\Resources\Summary\ProjectSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'industry' => $this->industry,
            'city' => $this->city,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'last_interaction_at' => $this->last_interaction_at,

            // Computed (withSum/withCount in ClientService — see comment there)
            'revenue' => (float) ($this->revenue_total ?? 0),
            'active_projects_count' => (int) ($this->active_projects_count ?? 0),

            'account_manager_id' => $this->account_manager_id,
            'account_manager' => $this->whenLoaded('accountManager', fn () => $this->accountManager ? new UserResource($this->accountManager) : null),

            'portal_account' => $this->whenLoaded('portalUsers', function () {
                $user = $this->portalUsers->first();

                return $user ? [
                    'email' => $user->email,
                    'portal_enabled' => $user->portal_enabled,
                    'must_change_password' => $user->must_change_password,
                    'last_login_at' => $user->last_login_at,
                ] : null;
            }),

            // Relationships — only present when eager-loaded via ?with=
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'payments' => DocumentPaymentResource::collection($this->whenLoaded('documentPayments')),
            'projects' => ProjectSummaryResource::collection($this->whenLoaded('projects')),
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
