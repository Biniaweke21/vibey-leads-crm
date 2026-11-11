<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;

class ShowClientRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('client'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'with' => $this->includeRule([
                'contacts', 'projects', 'documents', 'meetings',
                'notes', 'activities', 'attachments', 'reminders', 'payments', 'accountManager', 'portalUser',
            ]),
        ];
    }
}
