<?php

namespace App\Enums;

enum LicenseEventType: string
{
    case Created = 'created';
    case Renewed = 'renewed';
    case Suspended = 'suspended';
    case Reactivated = 'reactivated';
    case Expired = 'expired';
}
