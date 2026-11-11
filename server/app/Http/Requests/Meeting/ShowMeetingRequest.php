<?php

namespace App\Http\Requests\Meeting;

use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;

class ShowMeetingRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('meeting'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'with' => $this->includeRule([
                'client', 'project', 'lead', 'attendees',
                'notes', 'activities', 'attachments', 'reminders',
            ]),
        ];
    }
}
