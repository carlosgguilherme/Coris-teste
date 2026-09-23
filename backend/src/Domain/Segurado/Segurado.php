<?php

declare(strict_types=1);

namespace App\Domain\Segurado;

use App\Domain\Exception\DomainException;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;

class Segurado
{
    private function __construct(
        private ?int $id,
        private string $nome,
        private readonly Cpf $cpf,
        private string $email,
        private DateTimeImmutable $dataNascimento,
    ) {
    }

    public static function novo(string $nome, Cpf $cpf, string $email, DateTimeImmutable $dataNascimento, DateTimeImmutable $hoje): self
    {
        $segurado = new self(null, '', $cpf, '', $dataNascimento);
        $segurado->atualizarDados($nome, $email, $dataNascimento, $hoje);

        return $segurado;
    }

    public static function restaurar(int $id, string $nome, Cpf $cpf, string $email, DateTimeImmutable $dataNascimento): self
    {
        return new self($id, $nome, $cpf, $email, $dataNascimento);
    }

    /** @return string[] descrição do que mudou */
    public function atualizarDados(string $nome, string $email, DateTimeImmutable $dataNascimento, DateTimeImmutable $hoje): array
    {
        $nome = trim($nome);
        $email = mb_strtolower(trim($email));

        if ($nome === '') {
            throw new DomainException('O nome do segurado é obrigatório.', 'seguradoNome');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('E-mail do segurado inválido.', 'seguradoEmail');
        }

        if ($dataNascimento > $hoje) {
            throw new DomainException('A data de nascimento não pode ser futura.', 'seguradoNascimento');
        }

        $alteracoes = [];

        if ($this->nome !== '' && $this->nome !== $nome) {
            $alteracoes[] = "Nome do segurado: {$this->nome} → {$nome}";
        }

        if ($this->email !== '' && $this->email !== $email) {
            $alteracoes[] = "E-mail do segurado: {$this->email} → {$email}";
        }

        if ($this->id !== null && $this->dataNascimento != $dataNascimento) {
            $alteracoes[] = sprintf(
                'Nascimento do segurado: %s → %s',
                $this->dataNascimento->format('d/m/Y'),
                $dataNascimento->format('d/m/Y'),
            );
        }

        $this->nome = $nome;
        $this->email = $email;
        $this->dataNascimento = $dataNascimento;

        return $alteracoes;
    }

    public function idadeEm(DateTimeImmutable $data): int
    {
        return $this->dataNascimento->diff($data)->y;
    }

    public function definirId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function cpf(): Cpf
    {
        return $this->cpf;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function dataNascimento(): DateTimeImmutable
    {
        return $this->dataNascimento;
    }
}
