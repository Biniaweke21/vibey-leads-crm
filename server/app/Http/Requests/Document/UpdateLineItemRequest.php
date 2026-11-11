<?php

namespace App\Http\Requests\Document;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLineItemRequest extends FormRequest
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
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['sometimes', 'nullable', 'numeric', 'min:0', $this->discountWithinSubtotal()],
        ];
    }

    /** Falls back to the existing line item's stored quantity/unit_price for a partial update. */
    private function discountWithinSubtotal(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $lineItem = $this->route('lineItem');
            $qty = (float) $this->input('quantity', $lineItem?->quantity ?? 0);
            $price = (float) $this->input('unit_price', $lineItem?->unit_price ?? 0);

            if ((float) $value > $qty * $price) {
                $fail('The discount cannot exceed the line item subtotal.');
            }
        };
    }
}
