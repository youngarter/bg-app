<?php

namespace App\Enums;

enum OwnerType: string
{
    case PersonnePhysique = 'personne_physique';
    case PersonneMorale = 'personne_morale';

    public function label(): string
    {
        return match ($this) {
            self::PersonnePhysique => 'Personne physique',
            self::PersonneMorale => 'Personne morale / Société',
        };
    }
}
