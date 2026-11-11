<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityFeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'module' => $this->resource['module'],
            'description' => $this->resource['description'],
            'user' => $this->resource['user'],
            'created_at' => $this->resource['created_at'],
        ];
    }
}
