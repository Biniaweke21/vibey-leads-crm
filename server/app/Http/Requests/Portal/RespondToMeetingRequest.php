<?php

namespace App\Http\Requests\Portal;

use App\Enums\MeetingClientResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ownership of {meeting} and its Upcoming-only rule are checked in ClientPortalService (404/422, per the portal's IDOR requirement). */
class RespondToMeetingRequest extends FormRequest
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
            'response' => ['required', Rule::enum(MeetingClientResponse::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
