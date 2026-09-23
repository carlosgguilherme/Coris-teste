<?php

declare(strict_types=1);

namespace App\Application\Premio;

use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Segurado\Segurado;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Dinheiro;

final class CalculadoraPremioViagem implements CalculadoraPremio
{
    public function calcular(Plano $plano, Destino $destino, Vigencia $vigencia, Segurado $segurado): Dinheiro
    {
        $idade = $segurado->idadeEm($vigencia->inicio);

        return $plano->valorDiaria()
            ->multiplicar($vigencia->dias())
            ->aplicarPercentual($destino->percentualRisco())
            ->aplicarPercentual($this->percentualIdade($idade));
    }

    private function percentualIdade(int $idade): int
    {
        return match (true) {
            $idade >= 75 => 250,
            $idade >= 60 => 160,
            default => 100,
        };
    }
}
