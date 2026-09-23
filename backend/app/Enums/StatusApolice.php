<?php

namespace App\Enums;

enum StatusApolice: string
{
    case Ativa = 'ativa';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Ativa => 'Ativa',
            self::Cancelada => 'Cancelada',
        };
    }
}
