<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Apolice\ApoliceService;
use App\Domain\Apolice\FiltroApolices;
use App\Domain\Apolice\StatusApolice;
use App\Http\Request;
use App\Http\Resource\ApoliceResource;
use App\Http\Resource\EndossoResource;
use App\Http\Response;

final class ApoliceController
{
    public function __construct(private readonly ApoliceService $service)
    {
    }

    public function index(Request $request): Response
    {
        $pagina = $this->service->listar(new FiltroApolices(
            busca: $request->query('busca'),
            status: StatusApolice::tryFrom((string) $request->query('status')),
            pagina: (int) ($request->query('pagina') ?? 1),
            porPagina: (int) ($request->query('porPagina') ?? FiltroApolices::POR_PAGINA_PADRAO),
        ));

        return Response::json([
            'dados' => ApoliceResource::collection($pagina->itens),
            'paginacao' => [
                'pagina' => $pagina->pagina,
                'porPagina' => $pagina->porPagina,
                'total' => $pagina->total,
                'totalPaginas' => $pagina->totalPaginas(),
            ],
        ]);
    }

    public function resumo(): Response
    {
        return Response::json($this->service->resumo());
    }

    public function show(Request $request, int $id): Response
    {
        return Response::json(ApoliceResource::toArray($this->service->buscar($id)));
    }

    public function endossos(Request $request, int $id): Response
    {
        return Response::json(EndossoResource::collection($this->service->endossos($id)));
    }

    public function store(Request $request): Response
    {
        $apolice = $this->service->criar($request->json());

        return Response::json(ApoliceResource::toArray($apolice), 201);
    }

    public function update(Request $request, int $id): Response
    {
        $apolice = $this->service->atualizar($id, $request->json(), $request->usuario['email']);

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
