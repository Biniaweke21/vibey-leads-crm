<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps ClientPortalService::dashboard()'s array — not an Eloquent model. */
class ClientDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $client = $this->resource['company'];

        return [
            'welcome_name' => $this->resource['welcome_name'],
            'company' => [
                'id' => $client->id,
                'name' => $client->name,
                'industry' => $client->industry,
                'city' => $client->city,
                'status' => $client->status->value,
                'status_label' => $client->status->label(),
            ],
            'documents_count' => $this->resource['documents_count'],
            'active_projects' => ClientProjectResource::collection($this->resource['active_projects']),
            'recent_documents' => ClientDocumentResource::collection($this->resource['recent_documents']),
            'outstanding_invoices' => ClientDocumentResource::collection($this->resource['outstanding_invoices']),
            'outstanding_total' => $this->resource['outstanding_total'],
            'upcoming_meetings' => ClientMeetingResource::collection($this->resource['upcoming_meetings']),
            'recent_activity' => ActivityResource::collection($this->resource['recent_activity']),
        ];
    }
}
