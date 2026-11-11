<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Requests\Concerns\ValidatesIncludes;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDocumentRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Document::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Search — document number, title, client name, project name.
            'search' => ['sometimes', 'string', 'max:255'],

            // Filters
            'type' => ['sometimes', 'string', $this->csvInRule(array_column(DocumentType::cases(), 'value'))],
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(DocumentStatus::cases(), 'value'))],
            'client_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'project_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'amount_min' => ['sometimes', 'numeric', 'min:0'],
            'amount_max' => ['sometimes', 'numeric', 'min:0', 'gte:amount_min'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            // Sorting
            'sort_by' => ['sometimes', Rule::in(['created_at', 'updated_at', 'total_amount', 'status', 'client_name', 'number'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            // Pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            // Relationships to eager-load (lineItems/payments are always
            // loaded regardless — see DocumentService — but stay listed
            // here for a consistent, self-documenting API).
            'with' => $this->includeRule([
                'client', 'project', 'lineItems', 'payments',
                'notes', 'activities', 'attachments', 'reminders',
            ]),
        ];
    }
}
