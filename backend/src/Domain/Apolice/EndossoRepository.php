<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

interface EndossoRepository
{
    /** @return Endosso[] */
    public function listarPorApolice(int $apoliceId): array;

    public function salvar(Endosso $endosso): void;
}
