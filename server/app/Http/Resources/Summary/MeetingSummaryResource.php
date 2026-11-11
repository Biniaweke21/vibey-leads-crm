<?php

namespace App\Http\Resources\Summary;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal placeholder — the Meetings module isn't built yet. Exposes just
 * enough for a Client's `?with=meetings` to render a list. Replace with the
 * real MeetingResource when that module is implemented.
 */
class MeetingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'scheduled_at' => $this->scheduled_at,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
