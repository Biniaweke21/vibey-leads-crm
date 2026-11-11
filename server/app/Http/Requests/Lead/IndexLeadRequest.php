<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Http\Requests\Concerns\ValidatesIncludes;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLeadRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Lead::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Search — company (business/company name), contact name, email, phone.
            'search' => ['sometimes', 'string', 'max:255'],

            // Filters
            'status' => ['sometimes', 'string', $this->csvInRule(array_column(LeadStatus::cases(), 'value'))],
            'priority' => ['sometimes', 'string', $this->csvInRule(array_column(LeadPriority::cases(), 'value'))],
            'assigned_to' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'industry' => ['sometimes', 'string', 'max:255'],
            'service' => ['sometimes', 'string', $this->csvInRule(Lead::SERVICE_OPTIONS)],
            'budget' => ['sometimes', 'string', $this->csvInRule(Lead::BUDGET_RANGES)],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            // Sorting
            'sort_by' => ['sometimes', Rule::in(['created_at', 'updated_at', 'business_name', 'budget', 'status'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            // Pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            // Relationships to eager-load
            'with' => $this->includeRule(['assignee', 'notes', 'activities', 'reminders', 'attachments']),
        ];
    }
}
