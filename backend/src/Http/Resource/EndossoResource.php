<?php

declare(strict_types=1);

namespace App\Http\Resource;

use App\Domain\Apolice\Endosso;

final class EndossoResource
{
    /** @param Endosso[] $endossos */
    public static function collection(array $endossos): array
    {
        return array_map(fn (Endosso $endosso) => [
            'id' => $endosso->id(),
            'numero' => $endosso->numero(),
            'alteracoes' => $endosso->alteracoes,
            'premioAnteriorCentavos' => $endosso->premioAnterior->centavos,
            'premioNovoCentavos' => $endosso->premioNovo->centavos,
            'diferencaCentavos' => $endosso->diferencaEmCentavos(),
            'usuario' => $endosso->usuario,
            'criadoEm' => $endosso->criadoEm->format(DATE_ATOM),
        ], $endossos);
    }
}
