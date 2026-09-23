<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

interface UsuarioRepository
{
    public function buscarPorEmail(string $email): ?Usuario;

    public function existeAlgum(): bool;

    public function salvar(Usuario $usuario): void;
}
