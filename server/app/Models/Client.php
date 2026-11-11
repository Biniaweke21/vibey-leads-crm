<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Models\Concerns\HasCrmRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasCrmRelations, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'industry', 'city', 'status', 'account_manager_id', 'last_interaction_at'];

    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
            'last_interaction_at' => 'datetime',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    /** Payments recorded against this client's own documents (invoices, etc.) — the real, actively-used payment ledger. */
    public function documentPayments(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentPayment::class, Document::class);
    }

    /** Only the payments that count toward revenue. Column qualified — `documents` also has its own `status`. */
    public function paidDocumentPayments(): HasManyThrough
    {
        return $this->documentPayments()->where('document_payments.status', PaymentStatus::Paid);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** Projects not yet completed — mirrors the frontend's activeForClient definition. */
    public function activeProjects(): HasMany
    {
        return $this->projects()->where('status', '!=', ProjectStatus::Completed);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    /** Client-portal user accounts for this company. */
    public function portalUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
