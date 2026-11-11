<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiAnalysisRequest extends FormRequest
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
            'project_summary' => ['required', 'string'],

            'tech_stack' => ['required', 'array'],
            'tech_stack.frontend.choice' => ['required', 'string'],
            'tech_stack.frontend.why' => ['required', 'string'],
            'tech_stack.backend.choice' => ['required', 'string'],
            'tech_stack.backend.why' => ['required', 'string'],
            'tech_stack.database.choice' => ['required', 'string'],
            'tech_stack.database.why' => ['required', 'string'],
            'tech_stack.hosting.choice' => ['required', 'string'],
            'tech_stack.hosting.why' => ['required', 'string'],
            'tech_stack.authentication.choice' => ['required', 'string'],
            'tech_stack.authentication.why' => ['required', 'string'],
            'tech_stack.integrations.choice' => ['required', 'string'],
            'tech_stack.integrations.why' => ['required', 'string'],

            'complexity' => ['required', 'array'],
            'complexity.level' => ['required', Rule::in(['Low', 'Medium', 'High', 'Enterprise'])],
            'complexity.explanation' => ['required', 'string'],

            'timeline' => ['required', 'array'],
            'timeline.total_duration' => ['required', 'string'],
            'timeline.phases' => ['required', 'array', 'min:1'],
            'timeline.phases.*.phase' => ['required', 'string'],
            'timeline.phases.*.duration' => ['required', 'string'],

            'missing_requirements' => ['present', 'array'],
            'missing_requirements.*' => ['string'],

            'questions' => ['present', 'array'],
            'questions.*' => ['string'],

            'risks' => ['present', 'array'],
            'risks.*.title' => ['required', 'string'],
            'risks.*.explanation' => ['required', 'string'],

            'quotation' => ['required', 'array'],
            'quotation.currency' => ['required', 'string'],
            'quotation.range_low' => ['required', 'numeric', 'min:0'],
            'quotation.range_high' => ['required', 'numeric', 'gte:quotation.range_low'],
            'quotation.reasoning' => ['present', 'array'],
            'quotation.reasoning.*' => ['string'],
        ];
    }
}
