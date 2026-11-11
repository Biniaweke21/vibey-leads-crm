<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case DiscoveryCall = 'discovery-call';
    case ProposalSent = 'proposal-sent';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case InDevelopment = 'in-development';
    case Delivered = 'delivered';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New Lead',
            self::Contacted => 'Contacted',
            self::DiscoveryCall => 'Discovery Call Scheduled',
            self::ProposalSent => 'Proposal Sent',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::InDevelopment => 'In Development',
            self::Delivered => 'Delivered',
            self::Maintenance => 'Maintenance / Retainer',
        };
    }

    /** Stages that count as won for reporting. */
    public static function wonStages(): array
    {
        return [self::Won, self::InDevelopment, self::Delivered, self::Maintenance];
    }
}
