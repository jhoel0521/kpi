<?php

namespace App\Enums;

enum EstadoPuestaEnMarcha: string
{
    case EN_MARCHA = 'en_marcha';
    case FINALIZADA = 'finalizada';

    public function label(): string
    {
        return match ($this) {
            self::EN_MARCHA => 'En Marcha',
            self::FINALIZADA => 'Finalizada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::EN_MARCHA => 'bg-green-100 text-green-800',
            self::FINALIZADA => 'bg-blue-100 text-blue-800',
        };
    }
}
