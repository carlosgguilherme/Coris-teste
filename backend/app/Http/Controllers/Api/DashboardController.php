<?php

namespace App\Http\Controllers\Api;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\Filtros;
use App\Services\Dashboard\Periodo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private const VISOES = [
        'visao-geral' => 'visaoGeral',
        'marketing' => 'marketing',
        'comercial' => 'comercial',
        'sinistros' => 'sinistros',
    ];

    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(Request $request, string $visao): JsonResponse
    {
        $filtros = $request->validate(
            [
                'periodo' => ['nullable', Rule::in(array_keys(Periodo::OPCOES))],
                'canal' => ['nullable', Rule::exists('canais', 'codigo')],
                'plano' => ['nullable', Rule::in(array_column(Plano::cases(), 'value'))],
                'destino' => ['nullable', Rule::in(array_column(Destino::cases(), 'value'))],
            ],
            [
                'periodo.in' => 'Período inválido.',
                'canal.exists' => 'Canal inválido.',
                'plano.in' => 'Plano inválido.',
                'destino.in' => 'Destino inválido.',
            ],
        );

        $metodo = self::VISOES[$visao];

        return response()->json(
            $this->service->filtrar(Filtros::de($filtros))->{$metodo}(Periodo::de($request->query('periodo'))),
        );
    }
}
