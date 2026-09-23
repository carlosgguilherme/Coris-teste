<?php

declare(strict_types=1);

namespace App\Application\Apolice;

interface GeradorNumeroApolice
{
    public function gerar(): string;
}
