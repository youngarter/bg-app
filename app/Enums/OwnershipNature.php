<?php

namespace App\Enums;

enum OwnershipNature: string
{
    case PleinePropriete = 'pleine_propriete';
    case Indivision = 'indivision';
    case Usufruit = 'usufruit';
    case NuePropriete = 'nue_propriete';

    public function isPrincipalAxis(): bool
    {
        return in_array($this, [self::PleinePropriete, self::Indivision], true);
    }

    public function isDismemberedAxis(): bool
    {
        return in_array($this, [self::Usufruit, self::NuePropriete], true);
    }
}
