<?php

namespace App\Services\Dashboard;

use App\Models\Canal;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

/** Filtros da dashboard (canal, plano e destino). Vazios = sem filtro. */
class Filtros
{
    public function __construct(
        public readonly ?int $canalId = null,
        public readonly ?string $plano = null,
        public readonly ?string $destino = null,
    ) {}

    /** @param array{canal?: ?string, plano?: ?string, destino?: ?string} $dados */
    public static function de(array $dados): self
    {
        $canal = $dados['canal'] ?? null;

        return new self(
            $canal ? Canal::where('codigo', $canal)->value('id') : null,
            $dados['plano'] ?? null,
            $dados['destino'] ?? null,
        );
    }

    public function ativo(): bool
    {
        return $this->canalId !== null || $this->plano !== null || $this->destino !== null;
    }

    /** Aplica os filtros numa tabela que tem canal_id, plano e destino (apolices ou cotacoes). */
    public function aplicar(EloquentBuilder|Builder $query, string $tabela): EloquentBuilder|Builder
    {
        return $query
            ->when($this->canalId, fn ($q) => $q->where("{$tabela}.canal_id", $this->canalId))
            ->when($this->plano, fn ($q) => $q->where("{$tabela}.plano", $this->plano))
            ->when($this->destino, fn ($q) => $q->where("{$tabela}.destino", $this->destino));
    }

    /** Para sinistros e atendimentos: filtra pela apólice a que pertencem. */
    public function pelaApolice(EloquentBuilder $query): EloquentBuilder
    {
        return $query->when($this->ativo(), fn ($q) => $q->whereHas('apolice', fn ($apolice) => $this->aplicar($apolice, 'apolices')));
    }
}
