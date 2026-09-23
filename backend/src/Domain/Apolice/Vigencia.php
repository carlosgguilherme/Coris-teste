<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Exception\DomainException;
use DateTimeImmutable;

final class Vigencia
{
    public const DIAS_MAXIMOS = 365;

    public function __construct(
        public readonly DateTimeImmutable $inicio,
        public readonly DateTimeImmutable $fim,
    ) {
        if ($fim < $inicio) {
            throw new DomainException('O fim da vigência deve ser igual ou posterior ao início.', 'fimVigencia');
        }

        if ($this->dias() > self::DIAS_MAXIMOS) {
            throw new DomainException(sprintf('A vigência máxima é de %d dias.', self::DIAS_MAXIMOS), 'fimVigencia');
        }
    }

    public function dias(): int
    {
        return $this->inicio->diff($this->fim)->days + 1;
    }

    public function garantirInicioAPartirDe(DateTimeImmutable $hoje): void
    {
        if ($this->inicio < $hoje) {
            throw new DomainException('O início da vigência não pode ser anterior a hoje.', 'inicioVigencia');
        }
    }

    public function igual(self $outra): bool
    {
        return $this->inicio == $outra->inicio && $this->fim == $outra->fim;
    }

    public function descricao(): string
    {
        return $this->inicio->format('d/m/Y') . ' a ' . $this->fim->format('d/m/Y');
    }
}
