<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Exception\DomainException;
use App\Domain\Shared\Dinheiro;
use DateTimeImmutable;

class Apolice
{
    private function __construct(
        private ?int $id,
        private string $numero,
        private Segurado $segurado,
        private Destino $destino,
        private Plano $plano,
        private Vigencia $vigencia,
        private Dinheiro $valorPremio,
        private StatusApolice $status,
        private DateTimeImmutable $criadoEm,
        private ?DateTimeImmutable $atualizadoEm = null,
    ) {
        self::garantirPremioValido($valorPremio);
    }

    public static function emitir(
        string $numero,
        Segurado $segurado,
        Destino $destino,
        Plano $plano,
        Vigencia $vigencia,
        Dinheiro $valorPremio,
    ): self {
        return new self(
            id: null,
            numero: $numero,
            segurado: $segurado,
            destino: $destino,
            plano: $plano,
            vigencia: $vigencia,
            valorPremio: $valorPremio,
            status: StatusApolice::Ativa,
            criadoEm: new DateTimeImmutable(),
        );
    }

    public static function restaurar(
        int $id,
        string $numero,
        Segurado $segurado,
        Destino $destino,
        Plano $plano,
        Vigencia $vigencia,
        Dinheiro $valorPremio,
        StatusApolice $status,
        DateTimeImmutable $criadoEm,
        ?DateTimeImmutable $atualizadoEm,
    ): self {
        return new self($id, $numero, $segurado, $destino, $plano, $vigencia, $valorPremio, $status, $criadoEm, $atualizadoEm);
    }

    public function atualizar(
        Segurado $segurado,
        Destino $destino,
        Plano $plano,
        Vigencia $vigencia,
        Dinheiro $valorPremio,
        StatusApolice $status,
    ): void {
        if ($this->status === StatusApolice::Cancelada && $status === StatusApolice::Cancelada) {
            throw new DomainException('Apólice cancelada não pode ser alterada. Reative-a primeiro.');
        }

        self::garantirPremioValido($valorPremio);

        $this->segurado = $segurado;
        $this->destino = $destino;
        $this->plano = $plano;
        $this->vigencia = $vigencia;
        $this->valorPremio = $valorPremio;
        $this->status = $status;
        $this->atualizadoEm = new DateTimeImmutable();
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('A apólice já possui identificador.');
        }

        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function numero(): string
    {
        return $this->numero;
    }

    public function segurado(): Segurado
    {
        return $this->segurado;
    }

    public function destino(): Destino
    {
        return $this->destino;
    }

    public function plano(): Plano
    {
        return $this->plano;
    }

    public function vigencia(): Vigencia
    {
        return $this->vigencia;
    }

    public function valorPremio(): Dinheiro
    {
        return $this->valorPremio;
    }

    public function status(): StatusApolice
    {
        return $this->status;
    }

    public function criadoEm(): DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function atualizadoEm(): ?DateTimeImmutable
    {
        return $this->atualizadoEm;
    }

    private static function garantirPremioValido(Dinheiro $valorPremio): void
    {
        if (!$valorPremio->ehPositivo()) {
            throw new DomainException('O valor do prêmio deve ser maior que zero.');
        }
    }
}
