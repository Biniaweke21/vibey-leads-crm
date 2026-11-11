<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

/** Takes the metadata returned by POST /api/uploads — no file upload here directly. */
class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'integer', 'min:0'],
            'url' => ['nullable', 'string', 'max:500'],
            'secure_url' => ['nullable', 'string', 'max:500'],
            'cloudinary_public_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
