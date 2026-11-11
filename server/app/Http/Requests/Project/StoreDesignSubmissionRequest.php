<?php

namespace App\Http\Requests\Project;

use App\Enums\DesignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDesignSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], // Title
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(DesignStatus::class)],
            'size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:500'], // Attachment reference
            'note' => ['nullable', 'string'],
        ];
    }
}
