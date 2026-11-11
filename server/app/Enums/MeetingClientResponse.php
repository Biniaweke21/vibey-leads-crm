<?php

namespace App\Enums;

/** Null on the Meeting model means the client hasn't responded yet. */
enum MeetingClientResponse: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Unavailable => 'Not Available',
        };
    }
}
