<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps a single merged timeline entry (see ClientPortalService::timeline()) — not an Eloquent model. */
class ClientTimelineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'module' => $this->resource['module'],
            'description' => $this->resource['description'],
            'date' => $this->resource['date'],
        ];
    }
}
