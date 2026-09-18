<?php

namespace App\Enums;

enum ExerciseState: string
{
    case Open = 'open';
    case Closing = 'closing';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouvert',
            self::Closing => 'En cours de clôture',
            self::Closed => 'Clôturé',
        };
    }
}
