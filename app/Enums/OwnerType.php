<?php

namespace App\Enums;

enum OwnerType: string
{
    case PersonnePhysique = 'personne_physique';
    case PersonneMorale = 'personne_morale';
}
