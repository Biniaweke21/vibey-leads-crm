<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LeadCollection extends ResourceCollection
{
    /**
     * Keep each item as a LeadResource object (not a manually-flattened
     * array) — MissingValue filtering for whenLoaded() relations only
     * resolves correctly through the resource's own jsonSerialize() chain,
     * which json_encode() invokes recursively once this reaches the
     * response.
     *
     * @return array<int, LeadResource>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn ($lead) => new LeadResource($lead))
            ->all();
    }
}
