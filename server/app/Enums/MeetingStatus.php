<?php

namespace App\Enums;

enum MeetingStatus: string
{
    case Upcoming = 'upcoming';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
