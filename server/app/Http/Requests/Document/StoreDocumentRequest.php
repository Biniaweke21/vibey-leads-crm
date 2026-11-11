<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DocumentType::class)],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:50', Rule::unique('documents', 'number')],
            'currency' => ['nullable', 'string', 'size:3'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            // Default tax rate applied to new line items that don't specify their own.
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'body' => ['nullable', 'array'],
            // `status`, `signature_status`, `signed_at`, `signed_by`, `version` are
            // intentionally not accepted here — they're managed exclusively by the
            // workflow actions (send/approve/reject/sign/expire/duplicate) in
            // DocumentService, never directly settable via generic create/update.
        ];
    }
}
