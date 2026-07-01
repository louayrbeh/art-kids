<?php

namespace App\Enum;

enum UserRole: string
{
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_PARENT = 'ROLE_PARENT';

    public function label(): string
    {
        return match ($this) {
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_PARENT => 'Parent',
        };
    }
}
