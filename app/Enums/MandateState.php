<?php

namespace App\Enums;

enum MandateState: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Expired = 'expired';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Suspended => 'Suspendu',
            self::Terminated => 'Résilié',
            self::Expired => 'Expiré',
            self::Closed => 'Clôturé',
        };
    }
}
