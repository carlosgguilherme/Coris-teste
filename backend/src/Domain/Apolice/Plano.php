<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

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

    public function valorDiaria(): float
    {
        return match ($this) {
            self::Essencial => 12.90,
            self::Plus => 24.90,
            self::Premium => 39.90,
        };
    }

    public function coberturaMedica(): int
    {
        return match ($this) {
            self::Essencial => 30_000,
            self::Plus => 60_000,
            self::Premium => 150_000,
        };
    }
}
