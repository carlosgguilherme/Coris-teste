<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\DomainException;
use App\Domain\Shared\Dinheiro;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DinheiroTest extends TestCase
{
    public function testMultiplicaSemPerderPrecisao(): void
    {
        $this->assertSame(2_490_000, Dinheiro::centavos(2_490)->multiplicar(1_000)->centavos);
    }

    #[DataProvider('percentuais')]
    public function testAplicaPercentualArredondandoMeioCentavoParaCima(int $centavos, int $percentual, int $esperado): void
    {
        $this->assertSame($esperado, Dinheiro::centavos($centavos)->aplicarPercentual($percentual)->centavos);
    }

    public function testNaoAceitaValorNegativo(): void
    {
        $this->expectException(DomainException::class);

        Dinheiro::centavos(-1);
    }

    public function testEhImutavel(): void
    {
        $original = Dinheiro::centavos(1_000);
        $original->multiplicar(3);

        $this->assertSame(1_000, $original->centavos);
    }

    public static function percentuais(): array
    {
        return [
            'mantém valor com 100%' => [1_290, 100, 1_290],
            'metade' => [1_290, 50, 645],
            'acréscimo de 30%' => [2_490, 130, 3_237],
            'arredonda 0,5 centavo para cima' => [1_235, 50, 618],
            'arredonda abaixo de 0,5 centavo para baixo' => [1_001, 130, 1_301],
        ];
    }
}
