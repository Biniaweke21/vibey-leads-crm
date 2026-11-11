<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role, // Position
            'email' => $this->email,
            'phone' => $this->phone,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
        ];
    }
}
