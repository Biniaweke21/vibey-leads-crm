<?php

namespace App\Models;

use App\Enums\DesignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'name', 'description', 'version', 'size', 'mime_type', 'url',
        'status', 'submitted_by', 'submitted_at', 'decided_by', 'decided_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => DesignStatus::class,
            'version' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
