<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\SignatureStatus;
use App\Models\Concerns\HasCrmRelations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasCrmRelations, HasFactory, SoftDeletes;

    protected $fillable = [
        'type', 'client_id', 'project_id', 'title', 'number', 'status', 'version',
        'currency', 'issue_date', 'due_date', 'tax_rate', 'signature_status',
        'signed_at', 'signed_by', 'body', 'created_by', 'include_stamp', 'include_signature',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'signature_status' => SignatureStatus::class,
            'body' => 'array',
            'issue_date' => 'date',
            'due_date' => 'date',
            'tax_rate' => 'decimal:2',
            'version' => 'integer',
            'signed_at' => 'datetime',
            'include_stamp' => 'boolean',
            'include_signature' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(DocumentLineItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DocumentPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * Sum of line items' subtotal (before discount/tax). Always computed
     * from current line items — never stale, nothing to "recalculate".
     */
    protected function subtotal(): Attribute
    {
        return Attribute::get(fn () => round($this->lineItems->sum(fn (DocumentLineItem $i) => $i->subtotal), 2));
    }

    protected function discountTotal(): Attribute
    {
        return Attribute::get(fn () => round($this->lineItems->sum(fn (DocumentLineItem $i) => (float) $i->discount), 2));
    }

    protected function taxTotal(): Attribute
    {
        return Attribute::get(fn () => round($this->lineItems->sum(fn (DocumentLineItem $i) => $i->taxAmount), 2));
    }

    /** Grand total: subtotal - discount + tax, summed from line items. */
    protected function total(): Attribute
    {
        return Attribute::get(fn () => round($this->lineItems->sum(fn (DocumentLineItem $i) => $i->total), 2));
    }

    /** Sum of payments recorded as paid. */
    protected function amountPaid(): Attribute
    {
        return Attribute::get(
            fn () => round((float) $this->payments->where('status', PaymentStatus::Paid)->sum('amount'), 2),
        );
    }

    protected function balanceDue(): Attribute
    {
        return Attribute::get(fn () => round($this->total - $this->amountPaid, 2));
    }
}
