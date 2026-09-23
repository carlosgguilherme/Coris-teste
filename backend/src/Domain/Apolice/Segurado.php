<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Exception\DomainException;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;

final class Segurado
{
    public function __construct(
        public readonly string $nome,
        public readonly Cpf $cpf,
        public readonly string $email,
        public readonly DateTimeImmutable $dataNascimento,
    ) {
        if (trim($nome) === '') {
            throw new DomainException('O nome do segurado é obrigatório.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('E-mail do segurado inválido.');
        }
    }

    public function idadeEm(DateTimeImmutable $data): int
    {
        return $this->dataNascimento->diff($data)->y;
    }
}
