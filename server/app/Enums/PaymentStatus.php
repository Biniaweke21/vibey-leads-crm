<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
