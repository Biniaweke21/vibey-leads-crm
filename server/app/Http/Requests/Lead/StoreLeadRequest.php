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

class StoreLeadRequest extends FormRequest
{
    /**
     * Public — this is also the landing page's anonymous "Request a Quote"
     * intake, not just an authenticated team action.
     */
    public function authorize(): bool
    {
        return $this->user() === null || $this->user()->can('create', Lead::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'integer', 'exists:users,id', $this->assigneeMustBeTeamMember()],

            // Contact
            'full_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'whatsapp' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'city' => ['nullable', 'string', 'max:120'],
            'preferred_contact' => ['nullable', Rule::in(Lead::CONTACT_METHODS)],

            // Business
            'business_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'social_links' => ['nullable', 'string', 'max:500'],
            'business_description' => ['nullable', 'string'],

            // Project scope
            'project_idea' => ['nullable', 'string'],
            'main_goal' => ['nullable', 'string'],
            'target_users' => ['nullable', 'string'],
            'problems' => ['nullable', 'string'],
            'core_features' => ['nullable', 'string'],

            // Multi-value intake
            'services' => ['nullable', 'array'],
            'services.*' => [Rule::in(Lead::SERVICE_OPTIONS)],
            'other_service' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'array'],
            'other_feature' => ['nullable', 'string', 'max:255'],
            'design_style' => ['nullable', Rule::in(Lead::DESIGN_STYLES)],
            'reference_links' => ['nullable', 'array'],
            'reference_links.*' => ['nullable', 'string', 'max:500'],

            'budget' => ['nullable', Rule::in(Lead::BUDGET_RANGES)],
            'timeline' => ['nullable', Rule::in(Lead::TIMELINE_OPTIONS)],
            'additional_notes' => ['nullable', 'string'],

            // Files picked in the request form — already uploaded to
            // Cloudinary via POST /uploads by the time this arrives; this is
            // just the returned metadata, attached to the Lead on create.
            'design_files' => ['nullable', 'array'],
            'design_files.*.name' => ['required', 'string', 'max:255'],
            'design_files.*.mime_type' => ['nullable', 'string', 'max:120'],
            'design_files.*.size' => ['nullable', 'integer', 'min:0'],
            'design_files.*.url' => ['nullable', 'string', 'max:500'],
            'design_files.*.secure_url' => ['nullable', 'string', 'max:500'],
            'design_files.*.cloudinary_public_id' => ['nullable', 'string', 'max:255'],
            'general_files' => ['nullable', 'array'],
            'general_files.*.name' => ['required', 'string', 'max:255'],
            'general_files.*.mime_type' => ['nullable', 'string', 'max:120'],
            'general_files.*.size' => ['nullable', 'integer', 'min:0'],
            'general_files.*.url' => ['nullable', 'string', 'max:500'],
            'general_files.*.secure_url' => ['nullable', 'string', 'max:500'],
            'general_files.*.cloudinary_public_id' => ['nullable', 'string', 'max:255'],

            // Pipeline (defaulted by the service when omitted)
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'priority' => ['nullable', Rule::enum(LeadPriority::class)],
            'ai_analysis_status' => ['nullable', Rule::enum(AiAnalysisStatus::class)],
            'stage_entered_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
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
