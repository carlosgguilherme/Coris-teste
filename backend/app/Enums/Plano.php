<?php

namespace App\Enums;

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

    public function diariaCentavos(): int
    {
        return match ($this) {
            self::Essencial => 1290,
            self::Plus => 2490,
            self::Premium => 3990,
        };
    }

    public function coberturaMedicaCentavos(): int
    {
        return match ($this) {
            self::Essencial => 3_000_000,
            self::Plus => 6_000_000,
            self::Premium => 15_000_000,
        };
    }
}
