<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLineItem extends Model
{
    use HasFactory;

    protected $fillable = ['document_id', 'description', 'quantity', 'unit_price', 'tax_rate', 'discount', 'sort_order'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount' => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** quantity × unit_price, before discount/tax. */
    protected function subtotal(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->quantity * (float) $this->unit_price, 2));
    }

    /** Tax applied to (subtotal - discount). */
    protected function taxAmount(): Attribute
    {
        return Attribute::get(
            fn () => round(($this->subtotal - (float) $this->discount) * ((float) $this->tax_rate / 100), 2),
        );
    }

    /** subtotal - discount + tax. Always computed — never stale. */
    protected function total(): Attribute
    {
        return Attribute::get(
            fn () => round($this->subtotal - (float) $this->discount + $this->taxAmount, 2),
        );
    }
}
