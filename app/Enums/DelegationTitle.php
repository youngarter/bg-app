<?php

namespace App\Enums;

enum DelegationTitle: string
{
    case ViceSyndic = 'vice_syndic';
    case Tresorier = 'tresorier';
    case Secretaire = 'secretaire';
    case Delegue = 'delegue';
}
