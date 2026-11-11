<?php

namespace App\Http\Requests\Document;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'status' => ['sometimes', Rule::enum(PaymentStatus::class)],
            'method' => ['sometimes', 'nullable', 'string', 'max:50'],
            'reference_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
