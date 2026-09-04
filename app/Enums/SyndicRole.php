<?php

namespace App\Enums;

enum SyndicRole: string
{
    case Gerant = 'gerant';
    case Gestionnaire = 'gestionnaire';
    case Comptable = 'comptable';
}
