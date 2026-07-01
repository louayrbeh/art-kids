<?php

namespace App\Enum;

enum SexeEnum: string
{
    case GARCON = 'GARCON';
    case FILLE = 'FILLE';

    public function label(): string
    {
        return match ($this) {
            self::GARCON => 'Garcon',
            self::FILLE => 'Fille',
        };
    }
}
