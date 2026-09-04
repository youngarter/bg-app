<?php

namespace App\Support;

use App\Models\Residence;

class ResidenceContext
{
    protected static ?Residence $current = null;

    public static function set(?Residence $residence): void
    {
        static::$current = $residence;
    }

    public static function get(): ?Residence
    {
        return static::$current;
    }

    public static function getId(): ?int
    {
        return static::$current?->id;
    }

    public static function forget(): void
    {
        static::$current = null;
    }
}
