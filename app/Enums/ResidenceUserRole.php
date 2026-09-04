<?php

namespace App\Enums;

enum ResidenceUserRole: string
{
    case Admin = 'admin';
    case Gerant = 'gerant';
    case Gestionnaire = 'gestionnaire';
    case Comptable = 'comptable';
    case PresidentConseil = 'president_conseil';
    case MembreConseil = 'membre_conseil';
    case Coproprietaire = 'coproprietaire';
}
