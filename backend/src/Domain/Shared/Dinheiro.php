<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\DomainException;

final class Dinheiro
{
    private function __construct(public readonly int $centavos)
    {
        if ($centavos < 0) {
            throw new DomainException('Valor monetário não pode ser negativo.');
        }
    }

    public static function centavos(int $centavos): self
    {
        return new self($centavos);
    }

    public function multiplicar(int $quantidade): self
    {
        return new self($this->centavos * $quantidade);
    }

    public function aplicarPercentual(int $percentual): self
    {
        return new self(intdiv($this->centavos * $percentual + 50, 100));
    }

    public function ehPositivo(): bool
    {
        return $this->centavos > 0;
    }

    public function igual(self $outro): bool
    {
        return $this->centavos === $outro->centavos;
    }

    public function formatado(): string
    {
        return 'R$ ' . number_format($this->centavos / 100, 2, ',', '.');
    }
}
