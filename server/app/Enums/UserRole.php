<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case TeamMember = 'team_member';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::TeamMember => 'Team Member',
            self::Client => 'Client',
        };
    }

    /** Internal team roles (everyone except clients). */
    public static function team(): array
    {
        return [self::Admin, self::TeamMember];
    }

    public function isTeam(): bool
    {
        return $this !== self::Client;
    }
}
