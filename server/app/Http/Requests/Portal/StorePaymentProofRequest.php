<?php

namespace App\Http\Requests\Portal;

use App\Enums\PaymentStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Amount + stage + the uploaded proof's metadata (from POST /api/uploads, same pattern as StoreProjectAttachmentRequest). Ownership of {document} is checked in ClientPortalService (404, not 403, per the portal's IDOR requirement). */
class StorePaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'stage' => ['required', Rule::enum(PaymentStage::class)],
            'name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'integer', 'min:0'],
            'url' => ['nullable', 'string', 'max:500'],
            'secure_url' => ['nullable', 'string', 'max:500'],
            'cloudinary_public_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
