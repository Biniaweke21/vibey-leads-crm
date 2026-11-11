<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectCollection extends ResourceCollection
{
    /**
     * Keep each item as a ProjectResource object (not a manually-flattened
     * array) — MissingValue filtering for whenLoaded() relations only
     * resolves correctly through the resource's own jsonSerialize() chain.
     * (Same fix applied to LeadCollection/ClientCollection.)
     *
     * @return array<int, ProjectResource>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn ($project) => new ProjectResource($project))
            ->all();
    }
}
