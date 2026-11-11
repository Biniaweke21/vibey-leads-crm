<?php

namespace App\Http\Requests\Portal;

use App\Enums\MeetingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMeetingRequest extends FormRequest
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
            // Matches the frontend's own Upcoming/Past tabs.
            'when' => ['sometimes', Rule::in(['upcoming', 'past'])],
            'status' => ['sometimes', 'string', Rule::in(array_column(MeetingStatus::cases(), 'value'))],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],

            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
