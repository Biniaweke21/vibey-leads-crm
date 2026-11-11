<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentCollection extends ResourceCollection
{
    /**
     * Keep each item as a DocumentResource object (not a manually-flattened
     * array) — MissingValue filtering for whenLoaded() relations only
     * resolves correctly through the resource's own jsonSerialize() chain.
     * (Same fix applied to Lead/Client/ProjectCollection.)
     *
     * @return array<int, DocumentResource>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn ($document) => new DocumentResource($document))
            ->all();
    }
}
