<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Grace = 'grace';
    case ReadOnly = 'read_only';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Grace => 'Période de grâce',
            self::ReadOnly => 'Lecture seule',
            self::Suspended => 'Suspendue',
        };
    }

    public function allowsRead(): bool
    {
        return $this !== self::Suspended;
    }

    public function allowsWrite(): bool
    {
        return in_array($this, [self::Active, self::Grace], true);
    }
}
