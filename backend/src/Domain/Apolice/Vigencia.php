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
            throw new DomainException('O fim da vigência não pode ser anterior ao início.');
        }

        if ($this->dias() > self::DIAS_MAXIMOS) {
            throw new DomainException(sprintf('A vigência não pode ultrapassar %d dias.', self::DIAS_MAXIMOS));
        }
    }

    public function dias(): int
    {
        return $this->inicio->diff($this->fim)->days + 1;
    }
}
