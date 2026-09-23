<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Auth\EmissorToken;
use App\Application\Auth\NaoAutenticadoException;
use App\Domain\Shared\Relogio;
use App\Domain\Usuario\Usuario;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

final class JwtEmissorToken implements EmissorToken
{
    private const ALGORITMO = 'HS256';

    public function __construct(
        private readonly string $segredo,
        private readonly Relogio $relogio,
        private readonly int $validadeEmSegundos = 28_800,
    ) {
        if (strlen($segredo) < 32) {
            throw new \InvalidArgumentException('JWT_SECRET deve ter pelo menos 32 caracteres.');
        }
    }

    public function emitir(Usuario $usuario): string
    {
        $agora = $this->relogio->agora()->getTimestamp();

        return JWT::encode([
            'sub' => $usuario->id(),
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'iat' => $agora,
            'exp' => $agora + $this->validadeEmSegundos,
        ], $this->segredo, self::ALGORITMO);
    }

    public function validar(string $token): array
    {
        try {
            JWT::$timestamp = $this->relogio->agora()->getTimestamp();
            $dados = JWT::decode($token, new Key($this->segredo, self::ALGORITMO));
        } catch (Throwable) {
            throw NaoAutenticadoException::tokenInvalido();
        }

        return ['nome' => (string) $dados->nome, 'email' => (string) $dados->email];
    }
}
