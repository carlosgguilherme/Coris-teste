<?php

declare(strict_types=1);

namespace App\Domain\Apolice;

use App\Domain\Exception\DomainException;
use App\Domain\Segurado\Segurado;
use App\Domain\Shared\Dinheiro;
use DateTimeImmutable;

class Apolice
{
    private function __construct(
        private ?int $id,
        private readonly string $numero,
        private readonly Segurado $segurado,
        private Destino $destino,
        private Plano $plano,
        private Vigencia $vigencia,
        private Dinheiro $valorPremio,
        private StatusApolice $status,
        private readonly DateTimeImmutable $criadoEm,
        private ?DateTimeImmutable $atualizadoEm = null,
        private ?DateTimeImmutable $excluidoEm = null,
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
        DateTimeImmutable $agora,
    ): self {
        $vigencia->garantirInicioAPartirDe($agora->setTime(0, 0));

        return new self(
            id: null,
            numero: $numero,
            segurado: $segurado,
            destino: $destino,
            plano: $plano,
            vigencia: $vigencia,
            valorPremio: $valorPremio,
            status: StatusApolice::Ativa,
            criadoEm: $agora,
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
        ?DateTimeImmutable $excluidoEm = null,
    ): self {
        return new self($id, $numero, $segurado, $destino, $plano, $vigencia, $valorPremio, $status, $criadoEm, $atualizadoEm, $excluidoEm);
    }

    /**
     * Uma apólice emitida não é editada livremente: toda alteração gera um endosso
     * com o histórico do que mudou e a diferença de prêmio.
     *
     * @param string[] $alteracoesSegurado
     */
    public function endossar(
        Destino $destino,
        Plano $plano,
        Vigencia $vigencia,
        Dinheiro $valorPremio,
        StatusApolice $status,
        array $alteracoesSegurado,
        string $usuario,
        DateTimeImmutable $agora,
    ): Endosso {
        if ($this->status === StatusApolice::Cancelada && $status === StatusApolice::Cancelada) {
            throw new DomainException('Apólice cancelada não pode ser alterada. Reative-a primeiro.', 'status');
        }

        if ($vigencia->inicio != $this->vigencia->inicio) {
            $vigencia->garantirInicioAPartirDe($agora->setTime(0, 0));
        }

        self::garantirPremioValido($valorPremio);

        $alteracoes = [
            ...$alteracoesSegurado,
            ...$this->descreverAlteracoes($destino, $plano, $vigencia, $valorPremio, $status),
        ];

        if ($alteracoes === []) {
            throw new DomainException('Nenhuma alteração foi feita na apólice.');
        }

        $endosso = Endosso::registrar($this, $alteracoes, $this->valorPremio, $valorPremio, $usuario, $agora);

        $this->destino = $destino;
        $this->plano = $plano;
        $this->vigencia = $vigencia;
        $this->valorPremio = $valorPremio;
        $this->status = $status;
        $this->atualizadoEm = $agora;

        return $endosso;
    }

    public function excluir(DateTimeImmutable $agora): void
    {
        if ($this->excluidoEm !== null) {
            throw new DomainException('A apólice já foi excluída.');
        }

        $this->excluidoEm = $agora;
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

    public function excluidoEm(): ?DateTimeImmutable
    {
        return $this->excluidoEm;
    }

    /** @return string[] */
    private function descreverAlteracoes(
        Destino $destino,
        Plano $plano,
        Vigencia $vigencia,
        Dinheiro $valorPremio,
        StatusApolice $status,
    ): array {
        $alteracoes = [];

        if ($destino !== $this->destino) {
            $alteracoes[] = "Destino: {$this->destino->label()} → {$destino->label()}";
        }

        if ($plano !== $this->plano) {
            $alteracoes[] = "Plano: {$this->plano->label()} → {$plano->label()}";
        }

        if (!$vigencia->igual($this->vigencia)) {
            $alteracoes[] = "Vigência: {$this->vigencia->descricao()} → {$vigencia->descricao()}";
        }

        if ($status !== $this->status) {
            $alteracoes[] = "Status: {$this->status->label()} → {$status->label()}";
        }

        if (!$valorPremio->igual($this->valorPremio)) {
            $alteracoes[] = "Prêmio: {$this->valorPremio->formatado()} → {$valorPremio->formatado()}";
        }

        return $alteracoes;
    }

    private static function garantirPremioValido(Dinheiro $valorPremio): void
    {
        if (!$valorPremio->ehPositivo()) {
            throw new DomainException('O valor do prêmio deve ser maior que zero.');
        }
    }
}
