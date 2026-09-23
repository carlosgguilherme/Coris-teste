<?php

declare(strict_types=1);

namespace App\Application\Premio;

use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Segurado;
use App\Domain\Apolice\Vigencia;

final class CalculadoraPremioViagem implements CalculadoraPremio
{
    public function calcular(Plano $plano, Destino $destino, Vigencia $vigencia, Segurado $segurado): float
    {
        $idade = $segurado->idadeEm($vigencia->inicio);

        $premio = $plano->valorDiaria()
            * $vigencia->dias()
            * $destino->fatorRisco()
            * $this->fatorIdade($idade);

        return round($premio, 2);
    }

    private function fatorIdade(int $idade): float
    {
        return match (true) {
            $idade >= 75 => 2.5,
            $idade >= 60 => 1.6,
            default => 1.0,
        };
    }
}
