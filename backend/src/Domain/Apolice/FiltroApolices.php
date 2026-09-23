<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

final class FiltroApolices
{
    public const POR_PAGINA_PADRAO = 10;
    public const POR_PAGINA_MAXIMO = 50;

    public readonly int $pagina;
    public readonly int $porPagina;

    public function __construct(
        public readonly ?string $busca = null,
        public readonly ?StatusApolice $status = null,
        int $pagina = 1,
        int $porPagina = self::POR_PAGINA_PADRAO,
    ) {
        $this->pagina = max(1, $pagina);
        $this->porPagina = min(max(1, $porPagina), self::POR_PAGINA_MAXIMO);
    }

    public function deslocamento(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }
}
