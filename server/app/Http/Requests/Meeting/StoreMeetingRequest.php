<?php

namespace App\Http\Requests\Meeting;

use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\UserRole;
use App\Models\Meeting;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Meeting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', Rule::enum(MeetingType::class)],
            'starts_at' => ['required', 'date'],
            // "Prevent end time before start time" — strictly after also rules
            // out a nonsensical zero-duration meeting.
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(MeetingStatus::class)],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'organizer_id' => ['nullable', 'integer', 'exists:users,id', $this->organizerMustBeTeamMember()],
        ];
    }

    private function organizerMustBeTeamMember(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }
            $user = User::find($value);
            if ($user && $user->role === UserRole::Client) {
                $fail('The organizer must be a team member, not a client.');
            }
        };
    }
}
