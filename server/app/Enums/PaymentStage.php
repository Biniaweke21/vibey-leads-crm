<?php

namespace App\Enums;

enum PaymentStage: string
{
    case Deposit = 'deposit';
    case MidProject = 'mid_project';
    case FinalDelivery = 'final_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::MidProject => 'Mid-Project',
            self::FinalDelivery => 'Final Delivery',
        };
    }
}
