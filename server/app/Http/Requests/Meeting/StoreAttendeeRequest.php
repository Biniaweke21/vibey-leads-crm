<?php

namespace App\Http\Requests\Meeting;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendeeRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id', $this->notAlreadyAttending()],
        ];
    }

    private function notAlreadyAttending(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $meeting = $this->route('meeting');
            if ($meeting->attendees()->where('user_id', $value)->exists()) {
                $fail('This user is already an attendee of this meeting.');
            }
        };
    }
}
