<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Usuario\Usuario;

interface EmissorToken
{
    public function emitir(Usuario $usuario): string;

    /**
     * @return array{nome: string, email: string}
     * @throws NaoAutenticadoException
     */
    public function validar(string $token): array;
}
