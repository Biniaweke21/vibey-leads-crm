<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;

class ShowProjectRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'with' => $this->includeRule([
                'client', 'members', 'milestones', 'tasks', 'designSubmissions',
                'documents', 'meetings', 'notes', 'activities', 'attachments', 'reminders',
            ]),
        ];
    }
}
