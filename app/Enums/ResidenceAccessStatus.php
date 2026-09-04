<?php

namespace App\Enums;

enum ResidenceAccessStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
}
