<?php

namespace App\Http\Requests\Document;

use App\Http\Requests\Concerns\ValidatesIncludes;
use Illuminate\Foundation\Http\FormRequest;

class ShowDocumentRequest extends FormRequest
{
    use ValidatesIncludes;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'with' => $this->includeRule([
                'client', 'project', 'lineItems', 'payments',
                'notes', 'activities', 'attachments', 'reminders',
            ]),
        ];
    }
}
