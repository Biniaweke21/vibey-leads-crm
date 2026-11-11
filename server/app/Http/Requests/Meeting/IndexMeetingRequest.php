<?php

namespace App\Http\Requests\Meeting;

use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Http\Requests\Concerns\ValidatesIncludes;
use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMeetingRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Meeting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Search — title, client name, project name, lead name, organizer name.
            'search' => ['sometimes', 'string', 'max:255'],

            // Filters
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(MeetingStatus::cases(), 'value'))],
            'type' => ['sometimes', 'string', $this->csvInRule(array_column(MeetingType::cases(), 'value'))],
            'client_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'project_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'organizer_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            // Date range filters the meeting's own start time, not created_at —
            // "when is it happening" is the meaningful question for a calendar.
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],

            // Sorting
            'sort_by' => ['sometimes', Rule::in(['starts_at', 'created_at', 'updated_at', 'status', 'client_name'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            // Pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            // Relationships to eager-load
            'with' => $this->includeRule([
                'client', 'project', 'lead', 'attendees',
                'notes', 'activities', 'attachments', 'reminders',
            ]),
        ];
    }
}
