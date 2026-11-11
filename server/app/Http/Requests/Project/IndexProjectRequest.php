<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectStatus;
use App\Http\Requests\Concerns\ValidatesIncludes;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProjectRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Project::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Search — project name, client name, description.
            'search' => ['sometimes', 'string', 'max:255'],

            // Filters
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(ProjectStatus::cases(), 'value'))],
            'client_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'member_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'budget_min' => ['sometimes', 'numeric', 'min:0'],
            'budget_max' => ['sometimes', 'numeric', 'min:0', 'gte:budget_min'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            // Sorting
            'sort_by' => ['sometimes', Rule::in(['name', 'budget', 'status', 'progress', 'created_at', 'updated_at'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            // Pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            // Relationships to eager-load
            'with' => $this->includeRule([
                'client', 'members', 'milestones', 'tasks', 'designSubmissions',
                'documents', 'meetings', 'notes', 'activities', 'attachments', 'reminders',
            ]),
        ];
    }
}
