<?php

namespace App\Http\Requests\Lead;

use App\Enums\AiAnalysisStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id', $this->assigneeMustBeTeamMember()],

            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'preferred_contact' => ['sometimes', 'nullable', Rule::in(Lead::CONTACT_METHODS)],

            'business_name' => ['sometimes', 'required', 'string', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'social_links' => ['sometimes', 'nullable', 'string', 'max:500'],
            'business_description' => ['sometimes', 'nullable', 'string'],

            'project_idea' => ['sometimes', 'nullable', 'string'],
            'main_goal' => ['sometimes', 'nullable', 'string'],
            'target_users' => ['sometimes', 'nullable', 'string'],
            'problems' => ['sometimes', 'nullable', 'string'],
            'core_features' => ['sometimes', 'nullable', 'string'],

            'services' => ['sometimes', 'array'],
            'services.*' => [Rule::in(Lead::SERVICE_OPTIONS)],
            'other_service' => ['sometimes', 'nullable', 'string', 'max:255'],
            'features' => ['sometimes', 'nullable', 'array'],
            'other_feature' => ['sometimes', 'nullable', 'string', 'max:255'],
            'design_style' => ['sometimes', 'nullable', Rule::in(Lead::DESIGN_STYLES)],
            'reference_links' => ['sometimes', 'nullable', 'array'],
            'reference_links.*' => ['nullable', 'string', 'max:500'],

            'budget' => ['sometimes', 'nullable', Rule::in(Lead::BUDGET_RANGES)],
            'timeline' => ['sometimes', 'nullable', Rule::in(Lead::TIMELINE_OPTIONS)],
            'additional_notes' => ['sometimes', 'nullable', 'string'],

            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'priority' => ['sometimes', Rule::enum(LeadPriority::class)],
            'ai_analysis_status' => ['sometimes', Rule::enum(AiAnalysisStatus::class)],
            'stage_entered_at' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    private function assigneeMustBeTeamMember(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null) {
                return;
            }
            $user = User::find($value);
            if ($user && $user->role === UserRole::Client) {
                $fail('The assigned user must be a team member, not a client.');
            }
        };
    }
}
