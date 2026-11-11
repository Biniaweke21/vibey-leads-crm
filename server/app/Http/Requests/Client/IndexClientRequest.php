<?php

namespace App\Http\Requests\Client;

use App\Enums\ClientStatus;
use App\Http\Requests\Concerns\ValidatesIncludes;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexClientRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Client::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Search — company name, contact name, email, phone, industry.
            'search' => ['sometimes', 'string', 'max:255'],

            // Filters
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(ClientStatus::cases(), 'value'))],
            'industry' => ['sometimes', 'string', 'max:255'],
            'account_manager_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'revenue_min' => ['sometimes', 'numeric', 'min:0'],
            'revenue_max' => ['sometimes', 'numeric', 'min:0', 'gte:revenue_min'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            // Sorting
            'sort_by' => ['sometimes', Rule::in(['name', 'revenue_total', 'created_at', 'updated_at', 'status'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            // Pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            // Relationships to eager-load
            'with' => $this->includeRule([
                'contacts', 'projects', 'documents', 'meetings',
                'notes', 'activities', 'attachments', 'reminders', 'payments', 'accountManager', 'portalUser',
            ]),
        ];
    }
}
