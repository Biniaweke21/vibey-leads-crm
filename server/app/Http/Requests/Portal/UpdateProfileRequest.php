<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the authenticated user's own account contact info and notification
 * preferences are updatable — no company/ownership or internal CRM fields
 * (e.g. Client status, ClientContact.is_primary) are accepted here.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],

            'notification_preferences' => ['sometimes', 'array'],
            'notification_preferences.project_updates' => ['sometimes', 'boolean'],
            'notification_preferences.invoices' => ['sometimes', 'boolean'],
            'notification_preferences.meeting_reminders' => ['sometimes', 'boolean'],
            'notification_preferences.marketing' => ['sometimes', 'boolean'],
        ];
    }
}
