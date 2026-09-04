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
}
