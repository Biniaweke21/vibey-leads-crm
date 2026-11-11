<?php

namespace App\Http\Requests\Document;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreLineItemRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0', $this->discountWithinSubtotal()],
        ];
    }

    private function discountWithinSubtotal(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $subtotal = (float) $this->input('quantity', 0) * (float) $this->input('unit_price', 0);
            if ((float) $value > $subtotal) {
                $fail('The discount cannot exceed the line item subtotal.');
            }
        };
    }
}
