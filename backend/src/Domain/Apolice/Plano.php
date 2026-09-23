<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Shared\Dinheiro;

enum Plano: string
{
    case Essencial = 'essencial';
    case Plus = 'plus';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Essencial => 'Essencial',
            self::Plus => 'Plus',
            self::Premium => 'Premium',
        };
    }

    public function valorDiaria(): Dinheiro
    {
        return Dinheiro::centavos(match ($this) {
            self::Essencial => 1_290,
            self::Plus => 2_490,
            self::Premium => 3_990,
        });
    }

    public function coberturaMedica(): Dinheiro
    {
        return Dinheiro::centavos(match ($this) {
            self::Essencial => 3_000_000,
            self::Plus => 6_000_000,
            self::Premium => 15_000_000,
        });
    }
}
