<?php

namespace App\Models;

use App\Enums\PaymentStage;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DocumentPayment extends Model
{
    use HasFactory;

    protected $fillable = ['document_id', 'amount', 'status', 'method', 'stage', 'reference_number', 'paid_at', 'notes'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'stage' => PaymentStage::class,
            'paid_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** The client's uploaded proof (screenshot/receipt) for a submitted payment — same generic Attachment table every other module uses. */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}
