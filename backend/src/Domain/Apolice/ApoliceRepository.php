<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

interface ApoliceRepository
{
    /** @return Apolice[] */
    public function listar(?string $busca = null, ?StatusApolice $status = null): array;

    public function buscarPorId(int $id): ?Apolice;

    public function salvar(Apolice $apolice): void;

    public function excluir(int $id): void;
}
