<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CotacaoRequest;
use App\Http\Requests\SalvarApoliceRequest;
use App\Http\Resources\ApoliceResource;
use App\Models\Apolice;
use App\Services\ApoliceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ApoliceController extends Controller
{
    public function __construct(private readonly ApoliceService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ApoliceResource::collection(
            $this->service->listar($request->query('busca'), $request->query('status'))
        );
    }

    public function resumo(): JsonResponse
    {
        return response()->json($this->service->resumo());
    }

    public function show(Apolice $apolice): ApoliceResource
    {
        return new ApoliceResource($apolice->load('segurado'));
    }

    public function store(SalvarApoliceRequest $request): JsonResponse
    {
        $apolice = $this->service->criar($request->validated());

        return (new ApoliceResource($apolice))->response()->setStatusCode(201);
    }

    public function update(SalvarApoliceRequest $request, Apolice $apolice): ApoliceResource
    {
        return new ApoliceResource($this->service->atualizar($apolice, $request->validated()));
    }

    public function destroy(Apolice $apolice): Response
    {
        $this->service->excluir($apolice);

        return response()->noContent();
    }

    public function cotacao(CotacaoRequest $request): JsonResponse
    {
        return response()->json($this->service->cotar($request->validated()));
    }
}
