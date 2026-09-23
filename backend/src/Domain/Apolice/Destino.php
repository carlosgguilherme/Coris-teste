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

    public function percentualRisco(): int
    {
        return match ($this) {
            self::Nacional => 50,
            self::AmericaDoSul => 100,
            self::Europa => 130,
            self::AmericaDoNorte => 140,
            self::Asia, self::Africa, self::Oceania => 150,
        };
    }
}
