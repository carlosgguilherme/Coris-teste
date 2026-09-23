<?php

namespace Tests\Unit;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Services\Premio\CalculadoraPremioViagem;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CalculadoraPremioViagemTest extends TestCase
{
    #[DataProvider('cenarios')]
    public function test_calcula_premio_por_plano_destino_dias_e_idade(Plano $plano, Destino $destino, string $nascimento, int $esperado): void
    {
        $premio = (new CalculadoraPremioViagem())->calcular(
            $plano,
            $destino,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-10'),
            Carbon::parse($nascimento),
        );

        $this->assertSame($esperado, $premio);
    }

    public static function cenarios(): array
    {
        return [
            'plus, europa, adulto' => [Plano::Plus, Destino::Europa, '1990-01-01', 32370],
            'premium, europa, adulto' => [Plano::Premium, Destino::Europa, '1990-01-01', 51870],
            'essencial, américa do sul, adulto' => [Plano::Essencial, Destino::AmericaDoSul, '1990-01-01', 12900],
            'plus, europa, 66 anos' => [Plano::Plus, Destino::Europa, '1960-01-01', 51792],
            'essencial, américa do sul, 81 anos' => [Plano::Essencial, Destino::AmericaDoSul, '1945-01-01', 32250],
        ];
    }
}
