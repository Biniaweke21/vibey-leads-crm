<?php

namespace App\Enums;

enum MeetingType: string
{
    case DiscoveryCall = 'discovery-call';
    case Kickoff = 'kickoff';
    case StrategySync = 'strategy-sync';
    case Review = 'review';
    case RetainerCheckin = 'retainer-checkin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DiscoveryCall => 'Discovery Call',
            self::Kickoff => 'Kickoff',
            self::StrategySync => 'Strategy Sync',
            self::Review => 'Review',
            self::RetainerCheckin => 'Retainer Check-in',
            self::Other => 'Other',
        };
    }
}
