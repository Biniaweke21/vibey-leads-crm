<?php

namespace App\Http\Requests\Portal;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDocumentRequest extends FormRequest
{
    use ValidatesIncludes;

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
            'type' => ['sometimes', 'string', $this->csvInRule(array_column(DocumentType::cases(), 'value'))],
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(DocumentStatus::cases(), 'value'))],
            'project_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            'sort_by' => ['sometimes', Rule::in(['created_at', 'due_date', 'status'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
