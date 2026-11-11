<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(DocumentType::class)],
            'client_id' => ['sometimes', 'required', 'integer', 'exists:clients,id'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'number' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('documents', 'number')->ignore($this->route('document'))],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'issue_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'body' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
