<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Shared\Pagina;

interface ApoliceRepository
{
    /** @return Pagina<Apolice> */
    public function listar(FiltroApolices $filtro): Pagina;

    /** @return array{total: int, ativas: int, premioAtivasCentavos: int} */
    public function resumo(): array;

    public function buscarPorId(int $id): ?Apolice;

    public function numeroExiste(string $numero): bool;

    public function salvar(Apolice $apolice): void;
}
