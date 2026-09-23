<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** @template T */
final class Pagina
{
    /** @param T[] $itens */
    public function __construct(
        public readonly array $itens,
        public readonly int $total,
        public readonly int $pagina,
        public readonly int $porPagina,
    ) {
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->total / $this->porPagina));
    }
}
