<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

enum Destino: string
{
    case Nacional = 'nacional';
    case AmericaDoSul = 'america_do_sul';
    case AmericaDoNorte = 'america_do_norte';
    case Europa = 'europa';
    case Asia = 'asia';
    case Africa = 'africa';
    case Oceania = 'oceania';

    public function label(): string
    {
        return match ($this) {
            self::Nacional => 'Nacional',
            self::AmericaDoSul => 'América do Sul',
            self::AmericaDoNorte => 'América do Norte',
            self::Europa => 'Europa',
            self::Asia => 'Ásia',
            self::Africa => 'África',
            self::Oceania => 'Oceania',
        };
    }

    public function fatorRisco(): float
    {
        return match ($this) {
            self::Nacional => 0.5,
            self::AmericaDoSul => 1.0,
            self::Europa => 1.3,
            self::AmericaDoNorte => 1.4,
            self::Asia, self::Africa, self::Oceania => 1.5,
        };
    }
}
