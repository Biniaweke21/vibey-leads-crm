<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_portal_account' => $this->boolean('create_portal_account', true),
            'send_welcome_email' => $this->boolean('send_welcome_email', true),
            'generate_temporary_password' => $this->boolean('generate_temporary_password', true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'create_portal_account' => ['required', 'boolean'],
            'send_welcome_email' => ['required', 'boolean'],
            'generate_temporary_password' => ['required', 'boolean'],
        ];
    }
}
