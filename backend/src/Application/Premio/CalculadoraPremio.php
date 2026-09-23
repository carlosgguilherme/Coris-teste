<?php

declare(strict_types=1);

namespace App\Application\Premio;

use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Segurado;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Dinheiro;

interface CalculadoraPremio
{
    public function calcular(Plano $plano, Destino $destino, Vigencia $vigencia, Segurado $segurado): Dinheiro;
}
