<?php

namespace App\Http\Requests\Portal;

use App\Enums\ProjectStatus;
use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProjectRequest extends FormRequest
{
    use ValidatesIncludes;

    /** Ownership scoping (client_id) happens in the Service, not here — the `role:client` route middleware is the gate. */
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
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(ProjectStatus::cases(), 'value'))],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            'sort_by' => ['sometimes', Rule::in(['name', 'progress', 'created_at', 'start_date'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
