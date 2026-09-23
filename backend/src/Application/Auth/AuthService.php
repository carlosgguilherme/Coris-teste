<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Usuario\UsuarioRepository;

final class AuthService
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly EmissorToken $emissorToken,
    ) {
    }

    /** @return array{token: string, usuario: array{nome: string, email: string}} */
    public function login(string $email, string $senha): array
    {
        $usuario = $this->usuarios->buscarPorEmail(mb_strtolower(trim($email)));

        if ($usuario === null || !$usuario->senhaConfere($senha)) {
            throw NaoAutenticadoException::credenciaisInvalidas();
        }

        return [
            'token' => $this->emissorToken->emitir($usuario),
            'usuario' => ['nome' => $usuario->nome, 'email' => $usuario->email],
        ];
    }

    /** @return array{nome: string, email: string} */
    public function autenticar(?string $cabecalhoAuthorization): array
    {
        if ($cabecalhoAuthorization === null || !str_starts_with($cabecalhoAuthorization, 'Bearer ')) {
            throw NaoAutenticadoException::tokenInvalido();
        }

        return $this->emissorToken->validar(substr($cabecalhoAuthorization, 7));
    }
}
