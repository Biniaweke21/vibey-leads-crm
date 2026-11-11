<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in-progress';
    case OnHold = 'on-hold';
    case Completed = 'completed';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Completed => 'Completed',
            self::Maintenance => 'Maintenance',
        };
    }
}
