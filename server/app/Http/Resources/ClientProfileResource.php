<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps ClientPortalService::profile()'s array — not an Eloquent model. */
class ClientProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $client = $this->resource['company'];
        $account = $this->resource['account'];

        return [
            'company' => [
                'id' => $client->id,
                'name' => $client->name,
                'industry' => $client->industry,
                'city' => $client->city,
                'status' => $client->status->value,
                'status_label' => $client->status->label(),
            ],
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'must_change_password' => $account->must_change_password,
            ],
            'primary_contact' => $this->resource['primary_contact'] ? new ContactResource($this->resource['primary_contact']) : null,
            'secondary_contacts' => ContactResource::collection($this->resource['secondary_contacts']),
            'notification_preferences' => $this->resource['notification_preferences'],
        ];
    }
}
