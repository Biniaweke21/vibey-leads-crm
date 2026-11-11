<?php

namespace App\Models\Concerns;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Note;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared polymorphic relations for entities that carry notes, timeline
 * activity, file attachments, and reminders (leads, clients, projects,
 * meetings, documents). Mirrors the reusable frontend cards.
 */
trait HasCrmRelations
{
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }
}
