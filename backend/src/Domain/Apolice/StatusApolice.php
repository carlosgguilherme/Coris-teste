<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

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
