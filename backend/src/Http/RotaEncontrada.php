<?php

declare(strict_types=1);

namespace App\Http;

final class RotaEncontrada
{
    /** @var callable */
    private $handler;

    public function __construct(
        callable $handler,
        private readonly array $parametros,
        public readonly bool $publica,
    ) {
        $this->handler = $handler;
    }

    public function executar(Request $request): Response
    {
        return ($this->handler)($request, ...$this->parametros);
    }
}
