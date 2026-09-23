<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

use App\Domain\Exception\DomainException;

final class Usuario
{
    private function __construct(
        private ?int $id,
        public readonly string $nome,
        public readonly string $email,
        private readonly string $senhaHash,
    ) {
    }

    public static function cadastrar(string $nome, string $email, string $senha): self
    {
        if (mb_strlen($senha) < 8) {
            throw new DomainException('A senha deve ter pelo menos 8 caracteres.', 'senha');
        }

        return new self(null, trim($nome), mb_strtolower(trim($email)), password_hash($senha, PASSWORD_DEFAULT));
    }

    public static function restaurar(int $id, string $nome, string $email, string $senhaHash): self
    {
        return new self($id, $nome, $email, $senhaHash);
    }

    public function senhaConfere(string $senha): bool
    {
        return password_verify($senha, $this->senhaHash);
    }

    public function senhaHash(): string
    {
        return $this->senhaHash;
    }

    public function definirId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }
}
