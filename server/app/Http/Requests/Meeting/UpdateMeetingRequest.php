<?php

namespace App\Http\Requests\Meeting;

use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', Rule::enum(MeetingType::class)],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', $this->endsAfterStart()],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meeting_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'internal_notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(MeetingStatus::class)],
            'client_id' => ['sometimes', 'nullable', 'integer', 'exists:clients,id'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'lead_id' => ['sometimes', 'nullable', 'integer', 'exists:leads,id'],
            'organizer_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id', $this->organizerMustBeTeamMember()],
        ];
    }

    /**
     * Falls back to the meeting's existing stored starts_at when the request
     * only touches ends_at (partial update) — the built-in `after:starts_at`
     * rule can't see stored values, only sibling input.
     */
    private function endsAfterStart(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }
            $meeting = $this->route('meeting');
            $startsAt = $this->input('starts_at', $meeting?->starts_at);
            if ($startsAt && strtotime($value) <= strtotime($startsAt)) {
                $fail('The end time must be after the start time.');
            }
        };
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
