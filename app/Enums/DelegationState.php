<?php

namespace App\Enums;

enum DelegationState: string
{
    case Active = 'active';
    case Revoquee = 'revoquee';
}
