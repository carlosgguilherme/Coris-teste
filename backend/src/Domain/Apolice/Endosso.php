<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Shared\Dinheiro;
use DateTimeImmutable;

final class Endosso
{
    /** @param string[] $alteracoes */
    private function __construct(
        private ?int $id,
        private readonly int $apoliceId,
        private ?int $numero,
        public readonly array $alteracoes,
        public readonly Dinheiro $premioAnterior,
        public readonly Dinheiro $premioNovo,
        public readonly string $usuario,
        public readonly DateTimeImmutable $criadoEm,
    ) {
    }

    /** @param string[] $alteracoes */
    public static function registrar(
        Apolice $apolice,
        array $alteracoes,
        Dinheiro $premioAnterior,
        Dinheiro $premioNovo,
        string $usuario,
        DateTimeImmutable $criadoEm,
    ): self {
        return new self(null, (int) $apolice->id(), null, $alteracoes, $premioAnterior, $premioNovo, $usuario, $criadoEm);
    }

    /** @param string[] $alteracoes */
    public static function restaurar(
        int $id,
        int $apoliceId,
        int $numero,
        array $alteracoes,
        Dinheiro $premioAnterior,
        Dinheiro $premioNovo,
        string $usuario,
        DateTimeImmutable $criadoEm,
    ): self {
        return new self($id, $apoliceId, $numero, $alteracoes, $premioAnterior, $premioNovo, $usuario, $criadoEm);
    }

    public function diferencaEmCentavos(): int
    {
        return $this->premioNovo->centavos - $this->premioAnterior->centavos;
    }

    public function definirIdentificacao(int $id, int $numero): void
    {
        $this->id = $id;
        $this->numero = $numero;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function apoliceId(): int
    {
        return $this->apoliceId;
    }

    public function numero(): ?int
    {
        return $this->numero;
    }
}
