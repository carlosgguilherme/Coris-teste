<?php

namespace App\Services\Premio;

use App\Enums\Destino;
use App\Enums\Plano;
use Carbon\CarbonInterface;

interface CalculadoraPremio
{
    /** Retorna o valor do prêmio em centavos. */
    public function calcular(
        Plano $plano,
        Destino $destino,
        CarbonInterface $inicio,
        CarbonInterface $fim,
        CarbonInterface $dataNascimento,
    ): int;
}
