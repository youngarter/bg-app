<?php

namespace App\Enums;

enum LotType: string
{
    case Appartement = 'appartement';
    case Magasin = 'magasin';
    case Bureau = 'bureau';
    case Parking = 'parking';
    case Cave = 'cave';
    case LocalTechnique = 'local_technique';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Appartement => 'Appartement',
            self::Magasin => 'Magasin / Commerce',
            self::Bureau => 'Bureau',
            self::Parking => 'Parking',
            self::Cave => 'Cave',
            self::LocalTechnique => 'Local technique',
            self::Autre => 'Autre',
        };
    }
}
