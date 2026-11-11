<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamPerformanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource['user_id'],
            'name' => $this->resource['name'],
            'role' => $this->resource['role'],
            'assigned_leads' => $this->resource['assigned_leads'],
            'won_clients' => $this->resource['won_clients'],
            'active_projects' => $this->resource['active_projects'],
            'revenue_managed' => $this->resource['revenue_managed'],
            'completed_tasks' => $this->resource['completed_tasks'],
        ];
    }
}
