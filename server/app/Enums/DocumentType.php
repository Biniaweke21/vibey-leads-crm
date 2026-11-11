<?php

namespace App\Enums;

enum DocumentType: string
{
    case Proposal = 'proposal';
    case Contract = 'contract';
    case Invoice = 'invoice';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
