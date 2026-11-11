<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\HasCrmRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasCrmRelations, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'lead_id', 'name', 'description', 'status', 'progress',
        'budget', 'start_date', 'end_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'progress' => 'integer',
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** The Lead this project was created from at conversion — null for manually created projects. */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** Team members. */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function designSubmissions(): HasMany
    {
        return $this->hasMany(DesignSubmission::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}
