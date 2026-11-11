<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Past = 'past';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
