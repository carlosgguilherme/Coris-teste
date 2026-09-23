<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Vigencia;
use App\Domain\Segurado\Segurado;
use App\Domain\Shared\Cpf;
use App\Domain\Shared\Dinheiro;
use DateTimeImmutable;

final class Fabrica
{
    public static function segurado(string $nascimento = '1995-05-10'): Segurado
    {
        return Segurado::novo(
            'Carlos Pereira',
            Cpf::from('52998224725'),
            'carlos@email.com',
            new DateTimeImmutable($nascimento),
            new DateTimeImmutable('2026-09-20'),
        );
    }

    public static function vigencia(string $inicio = '2026-10-01', string $fim = '2026-10-10'): Vigencia
    {
        return new Vigencia(new DateTimeImmutable($inicio), new DateTimeImmutable($fim));
    }

    public static function apolice(int $premioCentavos = 32_370): Apolice
    {
        $apolice = Apolice::emitir(
            'CRS-2026-TESTE',
            self::segurado(),
            Destino::Europa,
            Plano::Plus,
            self::vigencia(),
            Dinheiro::centavos($premioCentavos),
            new DateTimeImmutable('2026-09-20 10:00:00'),
        );
        $apolice->definirId(1);

        return $apolice;
    }
}
