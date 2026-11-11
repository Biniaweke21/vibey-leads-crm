<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Generic {label, value} data point — reused by every Analytics chart endpoint. */
class ChartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->resource['label'],
            'value' => $this->resource['value'],
        ];
    }
}
