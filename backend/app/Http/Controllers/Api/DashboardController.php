<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
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
        $request->validate(
            ['periodo' => ['nullable', Rule::in(array_keys(Periodo::OPCOES))]],
            ['periodo.in' => 'Período inválido.'],
        );

        $metodo = self::VISOES[$visao];

        return response()->json($this->service->{$metodo}(Periodo::de($request->query('periodo'))));
    }
}
