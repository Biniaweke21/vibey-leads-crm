<?php

namespace App\Models;

use App\Enums\MeetingClientResponse;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Concerns\HasCrmRelations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasCrmRelations, HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'type', 'starts_at', 'ends_at', 'timezone',
        'location', 'meeting_url', 'internal_notes', 'status',
        'client_id', 'project_id', 'lead_id', 'organizer_id',
        'client_response', 'client_response_note', 'client_responded_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MeetingType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => MeetingStatus::class,
            'client_response' => MeetingClientResponse::class,
            'client_responded_at' => 'datetime',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    /** Always derived from starts_at/ends_at — never stored, never stale. */
    protected function durationMinutes(): Attribute
    {
        return Attribute::get(
            fn () => $this->ends_at ? $this->starts_at->diffInMinutes($this->ends_at) : null,
        );
    }

    /** Whether this meeting is eligible to auto-transition to Completed. */
    public function isDueForCompletion(): bool
    {
        if ($this->status !== MeetingStatus::Upcoming) {
            return false;
        }

        $reference = $this->ends_at ?? $this->starts_at;

        return $reference->isPast();
    }
}
