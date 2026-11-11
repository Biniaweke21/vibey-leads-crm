<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps AnalyticsService::dashboard()'s KPI array — not an Eloquent model. */
class AnalyticsDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_leads' => $this->resource['total_leads'],
            'active_clients' => $this->resource['active_clients'],
            'active_projects' => $this->resource['active_projects'],
            'total_revenue' => $this->resource['total_revenue'],
            'proposal_conversion_rate' => $this->resource['proposal_conversion_rate'],
            'invoice_collection_rate' => $this->resource['invoice_collection_rate'],
            'average_project_value' => $this->resource['average_project_value'],
            'average_lead_value' => $this->resource['average_lead_value'],
        ];
    }
}
