<?php

namespace App\Enums;

enum SignatureStatus: string
{
    case Unsigned = 'unsigned';
    case ClientSigned = 'client-signed';
    case FullySigned = 'fully-signed';

    public function label(): string
    {
        return match ($this) {
            self::Unsigned => 'Unsigned',
            self::ClientSigned => 'Client Signed',
            self::FullySigned => 'Fully Signed',
        };
    }
}
