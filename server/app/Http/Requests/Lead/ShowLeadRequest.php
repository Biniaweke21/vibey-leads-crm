<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;

class ShowLeadRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('lead'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'with' => $this->includeRule(['assignee', 'notes', 'activities', 'reminders', 'attachments']),
        ];
    }
}
