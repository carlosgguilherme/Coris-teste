<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Premio\CalculadoraPremioViagem;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Segurado;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalculadoraPremioViagemTest extends TestCase
{
    #[DataProvider('cenarios')]
    public function testCalculaPremioPorPlanoDestinoDiasEIdade(
        Plano $plano,
        Destino $destino,
        string $nascimento,
        int $esperadoCentavos,
    ): void {
        $vigencia = new Vigencia(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-10'));
        $segurado = new Segurado('Segurado Teste', Cpf::from('52998224725'), 'teste@email.com', new DateTimeImmutable($nascimento));

        $premio = (new CalculadoraPremioViagem())->calcular($plano, $destino, $vigencia, $segurado);

        $this->assertSame($esperadoCentavos, $premio->centavos);
    }

    public static function cenarios(): array
    {
        return [
            'essencial, américa do sul, adulto' => [Plano::Essencial, Destino::AmericaDoSul, '1990-01-01', 12_900],
            'plus, europa, adulto' => [Plano::Plus, Destino::Europa, '1990-01-01', 32_370],
            'premium, nacional, adulto' => [Plano::Premium, Destino::Nacional, '1990-01-01', 19_950],
            'plus, europa, 60+ anos' => [Plano::Plus, Destino::Europa, '1960-01-01', 51_792],
            'essencial, américa do sul, 75+ anos' => [Plano::Essencial, Destino::AmericaDoSul, '1945-01-01', 32_250],
        ];
    }
}
