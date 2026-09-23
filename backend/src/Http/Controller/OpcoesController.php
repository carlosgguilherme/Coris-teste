<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\StatusApolice;
use App\Http\Response;

final class OpcoesController
{
    public function __invoke(): Response
    {
        return Response::json([
            'planos' => array_map(fn (Plano $plano) => [
                'valor' => $plano->value,
                'label' => $plano->label(),
                'valorDiaria' => $plano->valorDiaria(),
                'coberturaMedica' => $plano->coberturaMedica(),
            ], Plano::cases()),
            'destinos' => array_map(fn (Destino $destino) => [
                'valor' => $destino->value,
                'label' => $destino->label(),
            ], Destino::cases()),
            'status' => array_map(fn (StatusApolice $status) => [
                'valor' => $status->value,
                'label' => $status->label(),
            ], StatusApolice::cases()),
        ]);
    }
}
