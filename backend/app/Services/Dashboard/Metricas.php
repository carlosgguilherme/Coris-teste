<?php

namespace App\Services\Dashboard;

/**
 * Fórmulas oficiais das dashboards. Ficam num lugar só para todas as telas usarem a mesma conta.
 * Retornam null quando não há base de cálculo (divisão por zero).
 */
class Metricas
{
    public static function ticketMedio(int $premioCentavos, int $apolices): ?int
    {
        return $apolices > 0 ? intdiv($premioCentavos, $apolices) : null;
    }

    /** Cotações convertidas ÷ cotações. */
    public static function conversao(int $convertidas, int $cotacoes): ?float
    {
        return self::razao($convertidas, $cotacoes);
    }

    /** Parte do prêmio que corresponde aos dias de viagem dentro do período. */
    public static function premioGanho(int $premioCentavos, int $diasNoPeriodo, int $diasDeVigencia): int
    {
        return $diasDeVigencia > 0 ? intdiv($premioCentavos * $diasNoPeriodo, $diasDeVigencia) : 0;
    }

    /** Custo dos sinistros ÷ prêmio ganho. */
    public static function sinistralidade(int $custoSinistrosCentavos, int $premioCentavos): ?float
    {
        return self::razao($custoSinistrosCentavos, $premioCentavos);
    }

    /** Sinistros avisados ÷ apólices emitidas. */
    public static function frequencia(int $sinistros, int $apolices): ?float
    {
        return self::razao($sinistros, $apolices);
    }

    /** Custo médio de cada sinistro. */
    public static function severidade(int $custoSinistrosCentavos, int $sinistros): ?int
    {
        return $sinistros > 0 ? intdiv($custoSinistrosCentavos, $sinistros) : null;
    }

    /** Sinistros negados ÷ sinistros finalizados (pagos + negados). */
    public static function taxaNegativa(int $negados, int $finalizados): ?float
    {
        return self::razao($negados, $finalizados);
    }

    /** (Prêmio gerado − investimento) ÷ investimento. */
    public static function roi(int $premioCentavos, int $investimentoCentavos): ?float
    {
        return $investimentoCentavos > 0 ? round(($premioCentavos - $investimentoCentavos) / $investimentoCentavos, 4) : null;
    }

    /** Quanto a campanha gastou para vender cada apólice: investimento ÷ apólices vendidas. */
    public static function custoPorApolice(int $investimentoCentavos, int $apolices): ?int
    {
        return $apolices > 0 ? intdiv($investimentoCentavos, $apolices) : null;
    }

    /** Parte de um total: 0,25 = 25%. */
    public static function participacao(int $parte, int $total): ?float
    {
        return self::razao($parte, $total);
    }

    /** % de promotores (nota 9 e 10) − % de detratores (nota 0 a 6), de −100 a 100. */
    public static function nps(int $promotores, int $detratores, int $respostas): ?int
    {
        return $respostas > 0 ? (int) round(($promotores - $detratores) * 100 / $respostas) : null;
    }

    private static function razao(int $parte, int $total): ?float
    {
        return $total > 0 ? round($parte / $total, 4) : null;
    }
}
