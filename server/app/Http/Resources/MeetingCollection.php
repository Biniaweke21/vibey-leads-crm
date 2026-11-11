<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MeetingCollection extends ResourceCollection
{
    /**
     * Keep each item as a MeetingResource object (not a manually-flattened
     * array) — MissingValue filtering for whenLoaded() relations only
     * resolves correctly through the resource's own jsonSerialize() chain.
     * (Same fix applied to Lead/Client/Project/DocumentCollection.)
     *
     * @return array<int, MeetingResource>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn ($meeting) => new MeetingResource($meeting))
            ->all();
    }
}
