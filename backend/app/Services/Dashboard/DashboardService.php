<?php

namespace App\Services\Dashboard;

use App\Enums\CanalAtendimento;
use App\Enums\Cobertura;
use App\Enums\Destino;
use App\Enums\Plano;
use App\Enums\StatusApolice;
use App\Enums\StatusCotacao;
use App\Enums\StatusSinistro;
use App\Models\Apolice;
use App\Models\Atendimento;
use App\Models\Campanha;
use App\Models\Cotacao;
use App\Models\FunilEvento;
use App\Models\Sinistro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Monta os números de cada visão da dashboard. As contas ficam em Metricas. */
class DashboardService
{
    private const CUSTO_SINISTRO = "SUM(CASE WHEN sinistros.status = 'pago' THEN valor_pago_centavos"
        ." WHEN sinistros.status = 'negado' THEN 0 ELSE valor_reclamado_centavos END)";

    private const FAIXAS_ANTECEDENCIA = [
        ['label' => 'Até 7 dias', 'ate' => 6],
        ['label' => '7 a 15 dias', 'ate' => 15],
        ['label' => '16 a 30 dias', 'ate' => 30],
        ['label' => '31 a 60 dias', 'ate' => 60],
        ['label' => 'Mais de 60 dias', 'ate' => PHP_INT_MAX],
    ];

    public function visaoGeral(Periodo $periodo): array
    {
        $atual = $this->indicadores($periodo);
        $anterior = $this->indicadores($periodo->anterior());

        return [
            'kpis' => collect($atual)
                ->map(fn ($valor, $chave) => ['valor' => $valor, 'anterior' => $anterior[$chave]])
                ->all(),
            'premioMensal' => $this->premioMensalComAnoAnterior(),
        ];
    }

    /** Marketing: a campanha converteu e o investimento valeu a pena? */
    public function marketing(Periodo $periodo): array
    {
        $campanhas = $this->campanhas($periodo);

        return [
            'kpis' => $this->indicadoresDeMarketing($periodo, $campanhas),
            'funil' => $this->funil($periodo),
            'porCanal' => $this->conversaoPorCanal($periodo),
            'campanhas' => $campanhas,
            'antecedencia' => $this->antecedenciaDaCompra($periodo),
        ];
    }

    /** Comercial: quanto vendemos, por onde e o quê. */
    public function comercial(Periodo $periodo): array
    {
        $atual = $this->vendas($periodo);
        $anterior = $this->vendas($periodo->anterior());
        $totalPremio = $atual['premioEmitidoCentavos'];

        $canais = $this->apolicesEmitidas($periodo)->toBase()
            ->leftJoin('canais', 'canais.id', '=', 'apolices.canal_id')
            ->groupBy('canais.nome')
            ->selectRaw('canais.nome as canal, COUNT(*) as apolices, SUM(valor_premio_centavos) as premio')
            ->orderByDesc('premio')
            ->get()
            ->map(fn ($linha) => [
                'canal' => $linha->canal ?? 'Painel interno',
                'apolices' => (int) $linha->apolices,
                'premioCentavos' => (int) $linha->premio,
                'ticketMedioCentavos' => Metricas::ticketMedio((int) $linha->premio, (int) $linha->apolices),
                'participacao' => Metricas::participacao((int) $linha->premio, $totalPremio),
            ]);

        $planos = $this->apolicesEmitidas($periodo)->toBase()
            ->groupBy('plano')
            ->selectRaw('plano, COUNT(*) as apolices, SUM(valor_premio_centavos) as premio')
            ->get()
            ->map(fn ($linha) => [
                'plano' => Plano::from($linha->plano)->label(),
                'apolices' => (int) $linha->apolices,
                'premioCentavos' => (int) $linha->premio,
                'ticketMedioCentavos' => Metricas::ticketMedio((int) $linha->premio, (int) $linha->apolices),
            ])
            ->sortByDesc('apolices')
            ->values();

        return [
            'kpis' => collect($atual)->map(fn ($valor, $chave) => ['valor' => $valor, 'anterior' => $anterior[$chave]])->all(),
            'canais' => $canais,
            'destinos' => $this->porDestino($periodo),
            'planos' => $planos,
        ];
    }

    public function sinistros(Periodo $periodo): array
    {
        $sinistros = $this->sinistrosAvisados($periodo)->toBase()
            ->selectRaw('COUNT(*) as quantidade, '.self::CUSTO_SINISTRO.' as custo')
            ->selectRaw("SUM(CASE WHEN sinistros.status = 'negado' THEN 1 ELSE 0 END) as negados")
            ->selectRaw("SUM(CASE WHEN sinistros.status IN ('pago', 'negado') THEN 1 ELSE 0 END) as finalizados")
            ->first();

        $apolices = $this->apolicesEmitidas($periodo)->count();

        return [
            'kpis' => [
                'sinistros' => (int) $sinistros->quantidade,
                'frequencia' => Metricas::frequencia((int) $sinistros->quantidade, $apolices),
                'severidadeCentavos' => Metricas::severidade((int) $sinistros->custo, (int) $sinistros->quantidade),
                'sinistralidade' => Metricas::sinistralidade((int) $sinistros->custo, $this->premioGanho($periodo)->sum()),
                'taxaNegativa' => Metricas::taxaNegativa((int) $sinistros->negados, (int) $sinistros->finalizados),
            ],
            'status' => $this->sinistrosPorStatus($periodo),
            'porDestino' => $this->sinistralidadePorDestino($periodo),
            'porCobertura' => $this->custoPorCobertura($periodo),
            'atendimento' => $this->atendimento($periodo),
        ];
    }

    private function indicadores(Periodo $periodo): array
    {
        $premio = (int) $this->apolicesEmitidas($periodo)->sum('valor_premio_centavos');
        $apolices = $this->apolicesEmitidas($periodo)->count();
        $custoSinistros = (int) $this->sinistrosAvisados($periodo)->selectRaw(self::CUSTO_SINISTRO.' as custo')->value('custo');
        $cotacoes = Cotacao::whereBetween('created_at', $periodo->intervalo());

        return [
            'premioEmitidoCentavos' => $premio,
            'apolices' => $apolices,
            'ticketMedioCentavos' => Metricas::ticketMedio($premio, $apolices),
            'conversao' => Metricas::conversao(
                (clone $cotacoes)->where('status', StatusCotacao::Convertida)->count(),
                $cotacoes->count(),
            ),
            'premioGanhoCentavos' => $this->premioGanho($periodo)->sum(),
            'sinistralidade' => Metricas::sinistralidade($custoSinistros, $this->premioGanho($periodo)->sum()),
            'nps' => $this->nps(Atendimento::whereBetween('inicio', $periodo->intervalo())),
        ];
    }

    /** Prêmio dos últimos 12 meses, mês a mês, ao lado do mesmo mês do ano anterior. */
    private function premioMensalComAnoAnterior(): Collection
    {
        $inicio = now()->startOfMonth()->subMonths(23);
        $porMes = Apolice::where('created_at', '>=', $inicio)
            ->where('status', '!=', StatusApolice::Cancelada)
            ->get(['created_at', 'valor_premio_centavos'])
            ->groupBy(fn (Apolice $apolice) => $apolice->created_at->format('Y-m'))
            ->map(fn (Collection $apolices) => $apolices->sum('valor_premio_centavos'));

        return collect(range(11, 0))->map(function (int $mesesAtras) use ($porMes) {
            $mes = now()->startOfMonth()->subMonths($mesesAtras);

            return [
                'mes' => $mes->format('Y-m'),
                'atualCentavos' => $porMes[$mes->format('Y-m')] ?? 0,
                'anoAnteriorCentavos' => $porMes[$mes->copy()->subYear()->format('Y-m')] ?? 0,
            ];
        });
    }

    private function funil(Periodo $periodo): Collection
    {
        $totais = FunilEvento::query()->toBase()->join('cotacoes', 'cotacoes.id', '=', 'funil_eventos.cotacao_id')
            ->whereBetween('cotacoes.created_at', $periodo->intervalo())
            ->groupBy('etapa')
            ->selectRaw('etapa, COUNT(DISTINCT cotacao_id) as total')
            ->pluck('total', 'etapa');

        return collect(StatusCotacao::etapasDoFunil())->map(fn (StatusCotacao $etapa) => [
            'etapa' => $etapa->value,
            'label' => $etapa->label(),
            'total' => (int) ($totais[$etapa->value] ?? 0),
        ]);
    }

    /** Cotações, conversão e apólices por canal de venda. */
    private function conversaoPorCanal(Periodo $periodo): Collection
    {
        return Cotacao::query()->toBase()
            ->join('canais', 'canais.id', '=', 'cotacoes.canal_id')
            ->whereBetween('cotacoes.created_at', $periodo->intervalo())
            ->groupBy('canais.nome')
            ->selectRaw("canais.nome as canal, COUNT(*) as total, SUM(CASE WHEN status = 'convertida' THEN 1 ELSE 0 END) as convertidas")
            ->get()
            ->map(fn ($linha) => [
                'canal' => $linha->canal,
                'cotacoes' => (int) $linha->total,
                'apolices' => (int) $linha->convertidas,
                'conversao' => Metricas::conversao((int) $linha->convertidas, (int) $linha->total),
            ])
            ->sortByDesc('conversao')
            ->values();
    }

    /**
     * Campanhas que estiveram no ar no período. Os números de cada uma consideram
     * a campanha inteira, do início ao fim, para avaliar se o investimento valeu.
     */
    private function campanhas(Periodo $periodo): Collection
    {
        $campanhas = Campanha::where('inicio', '<=', $periodo->fim->toDateString())
            ->where('fim', '>=', $periodo->inicio->toDateString())
            ->orderByDesc('inicio')
            ->get();

        $cotacoes = Cotacao::whereIn('campanha_id', $campanhas->pluck('id'))
            ->get(['campanha_id', 'status', 'created_at'])
            ->groupBy('campanha_id');

        $premios = Apolice::query()->toBase()
            ->whereIn('campanha_id', $campanhas->pluck('id'))
            ->where('status', '!=', StatusApolice::Cancelada)
            ->groupBy('campanha_id')
            ->selectRaw('campanha_id, SUM(valor_premio_centavos) as premio')
            ->pluck('premio', 'campanha_id');

        return $campanhas->map(function (Campanha $campanha) use ($cotacoes, $premios) {
            $daCampanha = $cotacoes[$campanha->id] ?? collect();
            $convertidas = $daCampanha->where('status', StatusCotacao::Convertida)->count();
            $premio = (int) ($premios[$campanha->id] ?? 0);

            return [
                'id' => $campanha->id,
                'nome' => $campanha->nome,
                'utmSource' => $campanha->utm_source,
                'inicio' => $campanha->inicio->toDateString(),
                'fim' => $campanha->fim->toDateString(),
                'cotacoes' => $daCampanha->count(),
                'apolices' => $convertidas,
                'conversao' => Metricas::conversao($convertidas, $daCampanha->count()),
                'premioCentavos' => $premio,
                'investimentoCentavos' => $campanha->investimento_centavos,
                'roi' => Metricas::roi($premio, $campanha->investimento_centavos),
                'custoPorApoliceCentavos' => Metricas::custoPorApolice($campanha->investimento_centavos, $convertidas),
                'semanas' => $this->semanasDaCampanha($campanha, $daCampanha),
            ];
        });
    }

    /** Cotações e apólices de cada semana em que a campanha esteve no ar. */
    private function semanasDaCampanha(Campanha $campanha, Collection $cotacoes): Collection
    {
        $porSemana = $cotacoes->groupBy(fn (Cotacao $cotacao) => $cotacao->created_at->startOfWeek()->toDateString());
        $semana = $campanha->inicio->copy()->startOfWeek();
        $ultima = $campanha->fim->min(now())->startOfWeek();
        $semanas = collect();

        while ($semana->lte($ultima)) {
            $daSemana = $porSemana[$semana->toDateString()] ?? collect();
            $semanas->push([
                'semana' => $semana->toDateString(),
                'cotacoes' => $daSemana->count(),
                'apolices' => $daSemana->where('status', StatusCotacao::Convertida)->count(),
            ]);
            $semana->addWeek();
        }

        return $semanas;
    }

    /** Números do topo da aba Marketing. */
    private function indicadoresDeMarketing(Periodo $periodo, Collection $campanhas): array
    {
        $cotacoes = Cotacao::whereBetween('created_at', $periodo->intervalo());
        $total = (clone $cotacoes)->count();
        $investimento = $campanhas->sum('investimentoCentavos');
        $premio = $campanhas->sum('premioCentavos');

        return [
            'cotacoes' => $total,
            'conversao' => Metricas::conversao($cotacoes->where('status', StatusCotacao::Convertida)->count(), $total),
            'investimentoCentavos' => $investimento,
            'premioCampanhasCentavos' => $premio,
            'roi' => Metricas::roi($premio, $investimento),
            'custoPorApoliceCentavos' => Metricas::custoPorApolice($investimento, $campanhas->sum('apolices')),
        ];
    }

    /** Números do topo da aba Comercial. */
    private function vendas(Periodo $periodo): array
    {
        $premio = (int) $this->apolicesEmitidas($periodo)->sum('valor_premio_centavos');
        $apolices = $this->apolicesEmitidas($periodo)->count();

        return [
            'premioEmitidoCentavos' => $premio,
            'apolices' => $apolices,
            'ticketMedioCentavos' => Metricas::ticketMedio($premio, $apolices),
            'canceladas' => Apolice::whereBetween('created_at', $periodo->intervalo())->where('status', StatusApolice::Cancelada)->count(),
        ];
    }

    private function porDestino(Periodo $periodo): Collection
    {
        return $this->apolicesEmitidas($periodo)->toBase()
            ->groupBy('destino')
            ->selectRaw('destino, COUNT(*) as apolices, SUM(valor_premio_centavos) as premio')
            ->orderByDesc('premio')
            ->get()
            ->map(fn ($linha) => [
                'destino' => Destino::from($linha->destino)->label(),
                'apolices' => (int) $linha->apolices,
                'premioCentavos' => (int) $linha->premio,
                'ticketMedioCentavos' => Metricas::ticketMedio((int) $linha->premio, (int) $linha->apolices),
            ]);
    }

    /** Quantos dias antes da viagem o cliente comprou o seguro. */
    private function antecedenciaDaCompra(Periodo $periodo): Collection
    {
        $antecedencias = $this->apolicesEmitidas($periodo)
            ->get(['created_at', 'inicio_vigencia'])
            ->map(fn (Apolice $apolice) => (int) $apolice->created_at->copy()->startOfDay()->diffInDays($apolice->inicio_vigencia, false));

        $faixas = collect(self::FAIXAS_ANTECEDENCIA)->map(fn ($faixa) => ['faixa' => $faixa['label'], 'apolices' => 0])->all();
        foreach ($antecedencias as $dias) {
            $indice = collect(self::FAIXAS_ANTECEDENCIA)->search(fn ($faixa) => $dias <= $faixa['ate']);
            $faixas[$indice]['apolices']++;
        }

        return collect($faixas);
    }

    private function sinistrosPorStatus(Periodo $periodo): Collection
    {
        $totais = $this->sinistrosAvisados($periodo)->toBase()
            ->groupBy('sinistros.status')
            ->selectRaw('sinistros.status, COUNT(*) as total')
            ->pluck('total', 'status');

        return collect(StatusSinistro::cases())->map(fn (StatusSinistro $status) => [
            'status' => $status->label(),
            'total' => (int) ($totais[$status->value] ?? 0),
        ]);
    }

    private function sinistralidadePorDestino(Periodo $periodo): Collection
    {
        $premios = $this->premioGanho($periodo, porDestino: true);

        $custos = $this->sinistrosAvisados($periodo)->toBase()
            ->join('apolices', 'apolices.id', '=', 'sinistros.apolice_id')
            ->groupBy('apolices.destino')
            ->selectRaw('apolices.destino, COUNT(*) as quantidade, '.self::CUSTO_SINISTRO.' as custo')
            ->get()
            ->keyBy('destino');

        return $premios->map(fn ($premio, $destino) => [
            'destino' => Destino::from($destino)->label(),
            'sinistros' => (int) ($custos[$destino]->quantidade ?? 0),
            'sinistralidade' => Metricas::sinistralidade((int) ($custos[$destino]->custo ?? 0), (int) $premio),
        ])->sortByDesc('sinistralidade')->values();
    }

    private function custoPorCobertura(Periodo $periodo): Collection
    {
        return $this->sinistrosAvisados($periodo)->toBase()
            ->groupBy('cobertura')
            ->selectRaw('cobertura, COUNT(*) as quantidade, '.self::CUSTO_SINISTRO.' as custo')
            ->orderByDesc('custo')
            ->get()
            ->map(fn ($linha) => [
                'cobertura' => Cobertura::from($linha->cobertura)->label(),
                'sinistros' => (int) $linha->quantidade,
                'custoCentavos' => (int) $linha->custo,
            ]);
    }

    private function atendimento(Periodo $periodo): array
    {
        $atendimentos = Atendimento::whereBetween('inicio', $periodo->intervalo());

        $porCanal = (clone $atendimentos)->toBase()
            ->groupBy('canal')
            ->selectRaw('canal, COUNT(*) as total, AVG(tempo_espera_seg) as espera, SUM(CASE WHEN dentro_sla THEN 1 ELSE 0 END) as no_prazo')
            ->get()
            ->map(fn ($linha) => [
                'canal' => CanalAtendimento::from($linha->canal)->label(),
                'atendimentos' => (int) $linha->total,
                'esperaMediaSeg' => (int) round($linha->espera),
                'sla' => round($linha->no_prazo / $linha->total, 4),
            ]);

        $total = (clone $atendimentos)->count();

        return [
            'nps' => $this->nps(clone $atendimentos),
            'atendimentos' => $total,
            'sla' => $total > 0 ? round((clone $atendimentos)->where('dentro_sla', true)->count() / $total, 4) : null,
            'porCanal' => $porCanal,
            'npsMensal' => $this->npsMensal(),
        ];
    }

    private function npsMensal(): Collection
    {
        $notas = Atendimento::where('inicio', '>=', now()->startOfMonth()->subMonths(11))
            ->whereNotNull('nps')
            ->get(['inicio', 'nps'])
            ->groupBy(fn (Atendimento $atendimento) => $atendimento->inicio->format('Y-m'));

        return collect(range(11, 0))->map(function (int $mesesAtras) use ($notas) {
            $mes = now()->startOfMonth()->subMonths($mesesAtras)->format('Y-m');
            $doMes = $notas[$mes] ?? collect();

            return [
                'mes' => $mes,
                'nps' => Metricas::nps($doMes->where('nps', '>=', 9)->count(), $doMes->where('nps', '<=', 6)->count(), $doMes->count()),
            ];
        });
    }

    private function nps(Builder $atendimentos): ?int
    {
        $notas = $atendimentos->whereNotNull('nps')
            ->selectRaw('COUNT(*) as respostas, SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) as promotores, SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END) as detratores')
            ->first();

        return Metricas::nps((int) $notas->promotores, (int) $notas->detratores, (int) $notas->respostas);
    }

    /**
     * Prêmio ganho no período (total ou por destino): cada apólice em vigor no período
     * contribui com a parte do prêmio proporcional aos seus dias de viagem dentro dele.
     */
    private function premioGanho(Periodo $periodo, bool $porDestino = false): Collection
    {
        [$inicio, $fim] = [$periodo->inicio->copy()->startOfDay(), $periodo->fim->copy()->startOfDay()];

        return Apolice::where('status', '!=', StatusApolice::Cancelada)
            ->where('inicio_vigencia', '<=', $fim->toDateString())
            ->where('fim_vigencia', '>=', $inicio->toDateString())
            ->get(['destino', 'inicio_vigencia', 'fim_vigencia', 'valor_premio_centavos'])
            ->groupBy(fn (Apolice $apolice) => $porDestino ? $apolice->destino->value : 'total')
            ->map(fn (Collection $apolices) => $apolices->sum(function (Apolice $apolice) use ($inicio, $fim) {
                $diasNoPeriodo = (int) $apolice->inicio_vigencia->max($inicio)->diffInDays($apolice->fim_vigencia->min($fim)) + 1;

                return Metricas::premioGanho($apolice->valor_premio_centavos, $diasNoPeriodo, $apolice->dias());
            }));
    }

    /** Apólices emitidas no período, sem as canceladas. */
    private function apolicesEmitidas(Periodo $periodo): Builder
    {
        return Apolice::query()
            ->whereBetween('apolices.created_at', $periodo->intervalo())
            ->where('apolices.status', '!=', StatusApolice::Cancelada);
    }

    private function sinistrosAvisados(Periodo $periodo): Builder
    {
        [$inicio, $fim] = $periodo->intervalo();

        return Sinistro::query()->whereBetween('data_aviso', [$inicio->toDateString(), $fim->toDateString()]);
    }
}
