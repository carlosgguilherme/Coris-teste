<?php

declare(strict_types=1);

namespace App\Application\Auth;

class NaoAutenticadoException extends \RuntimeException
{
    public static function credenciaisInvalidas(): self
    {
        return new self('E-mail ou senha inválidos.');
    }

    public static function tokenInvalido(): self
    {
        return new self('Sessão inválida ou expirada. Faça login novamente.');
    }
}
