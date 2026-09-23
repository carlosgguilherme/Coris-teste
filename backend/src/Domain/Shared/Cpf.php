<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\DomainException;

final class Cpf
{
    private function __construct(public readonly string $numero)
    {
    }

    public static function from(string $valor): self
    {
        $numero = self::somenteDigitos($valor);

        if (!self::isValid($numero)) {
            throw new DomainException('CPF inválido.');
        }

        return new self($numero);
    }

    public static function isValid(string $valor): bool
    {
        $cpf = self::somenteDigitos($valor);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    public function formatado(): string
    {
        return vsprintf('%s.%s.%s-%s', [
            substr($this->numero, 0, 3),
            substr($this->numero, 3, 3),
            substr($this->numero, 6, 3),
            substr($this->numero, 9, 2),
        ]);
    }

    private static function somenteDigitos(string $valor): string
    {
        return preg_replace('/\D/', '', $valor) ?? '';
    }
}
