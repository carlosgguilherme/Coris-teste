<?php

declare(strict_types=1);

namespace App\Application\Apolice;

final class GeradorNumeroAleatorio implements GeradorNumeroApolice
{
    public function gerar(): string
    {
        return sprintf('CRS-%s-%s', date('Y'), strtoupper(bin2hex(random_bytes(4))));
    }
}
