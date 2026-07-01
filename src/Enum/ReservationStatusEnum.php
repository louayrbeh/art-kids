<?php

namespace App\Enum;

enum ReservationStatusEnum: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRMEE = 'CONFIRMEE';
    case ANNULEE = 'ANNULEE';
    case TERMINEE = 'TERMINEE';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRMEE => 'Confirmee',
            self::ANNULEE => 'Annulee',
            self::TERMINEE => 'Terminee',
        };
    }
}
