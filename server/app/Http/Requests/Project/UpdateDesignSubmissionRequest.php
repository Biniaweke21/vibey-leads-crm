<?php

namespace App\Http\Requests\Project;

use App\Enums\DesignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignSubmissionRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'], // Title
            'description' => ['sometimes', 'nullable', 'string'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(DesignStatus::class)],
            'size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mime_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'url' => ['sometimes', 'nullable', 'string', 'max:500'], // Attachment reference
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
