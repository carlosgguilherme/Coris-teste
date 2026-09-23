<?php

declare(strict_types=1);

namespace App\Domain\Segurado;

use App\Domain\Shared\Cpf;

interface SeguradoRepository
{
    public function buscarPorCpf(Cpf $cpf): ?Segurado;

    public function salvar(Segurado $segurado): void;
}
