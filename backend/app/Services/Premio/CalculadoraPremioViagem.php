<?php

namespace App\Services\Premio;

use App\Enums\Destino;
use App\Enums\Plano;
use Carbon\CarbonInterface;

class CalculadoraPremioViagem implements CalculadoraPremio
{
    public function calcular(
        Plano $plano,
        Destino $destino,
        CarbonInterface $inicio,
        CarbonInterface $fim,
        CarbonInterface $dataNascimento,
    ): int {
        $dias = (int) $inicio->diffInDays($fim) + 1;
        $idade = (int) $dataNascimento->diffInYears($inicio);

        $valor = $plano->diariaCentavos() * $dias;
        $valor = $this->aplicarPercentual($valor, $destino->percentualRisco());

        return $this->aplicarPercentual($valor, $this->percentualIdade($idade));
    }

    private function percentualIdade(int $idade): int
    {
        return match (true) {
            $idade >= 75 => 250,
            $idade >= 60 => 160,
            default => 100,
        };
    }

    private function aplicarPercentual(int $centavos, int $percentual): int
    {
        return intdiv($centavos * $percentual + 50, 100);
    }
}
