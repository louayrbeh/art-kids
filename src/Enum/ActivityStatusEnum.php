<?php

namespace App\Enum;

enum ActivityStatusEnum: string
{
    case OUVERTE = 'OUVERTE';
    case COMPLETE = 'COMPLETE';
    case ANNULEE = 'ANNULEE';
    case TERMINEE = 'TERMINEE';

    public function label(): string
    {
        return match ($this) {
            self::OUVERTE => 'Ouverte',
            self::COMPLETE => 'Complete',
            self::ANNULEE => 'Annulee',
            self::TERMINEE => 'Terminee',
        };
    }
}
