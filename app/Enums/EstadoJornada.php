<?php

namespace App\Enums;

enum EstadoJornada: string
{
    case ACTIVA = 'activa';
    case FINALIZADA = 'finalizada';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::FINALIZADA => 'Finalizada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVA => 'text-green-600',
            self::FINALIZADA => 'text-gray-600',
        };
    }
}
