<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Apolice\ApoliceService;
use App\Http\Request;
use App\Http\Resource\ApoliceResource;
use App\Http\Response;

final class ApoliceController
{
    public function __construct(private readonly ApoliceService $service)
    {
    }

    public function index(Request $request): Response
    {
        $apolices = $this->service->listar($request->query('busca'), $request->query('status'));

        return Response::json(ApoliceResource::collection($apolices));
    }

    public function show(Request $request, int $id): Response
    {
        return Response::json(ApoliceResource::toArray($this->service->buscar($id)));
    }

    public function store(Request $request): Response
    {
        $apolice = $this->service->criar($request->json());

        return Response::json(ApoliceResource::toArray($apolice), 201);
    }

    public function update(Request $request, int $id): Response
    {
        $apolice = $this->service->atualizar($id, $request->json());

        return Response::json(ApoliceResource::toArray($apolice));
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->service->excluir($id);

        return Response::noContent();
    }

    public function cotacao(Request $request): Response
    {
        return Response::json($this->service->cotar($request->json()));
    }
}
