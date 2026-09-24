<?php

namespace App\Http\Controllers\Api;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Enums\StatusApolice;
use App\Http\Controllers\Controller;
use App\Models\Canal;
use Illuminate\Http\JsonResponse;

class OpcoesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'planos' => array_map(fn (Plano $plano) => [
                'valor' => $plano->value,
                'label' => $plano->label(),
                'valorDiariaCentavos' => $plano->diariaCentavos(),
                'coberturaMedicaCentavos' => $plano->coberturaMedicaCentavos(),
            ], Plano::cases()),
            'destinos' => array_map(fn (Destino $destino) => [
                'valor' => $destino->value,
                'label' => $destino->label(),
            ], Destino::cases()),
            'canais' => Canal::orderBy('id')->get()->map(fn (Canal $canal) => [
                'valor' => $canal->codigo,
                'label' => $canal->nome,
            ]),
            'status' => array_map(fn (StatusApolice $status) => [
                'valor' => $status->value,
                'label' => $status->label(),
            ], StatusApolice::cases()),
        ]);
    }
}
