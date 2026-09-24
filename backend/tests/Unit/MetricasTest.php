<?php

namespace Tests\Unit;

use App\Services\Dashboard\Metricas;
use PHPUnit\Framework\TestCase;

class MetricasTest extends TestCase
{
    public function test_ticket_medio_e_severidade(): void
    {
        $this->assertSame(40000, Metricas::ticketMedio(120000, 3));
        $this->assertSame(250000, Metricas::severidade(500000, 2));
        $this->assertNull(Metricas::ticketMedio(0, 0));
    }

    public function test_razoes_arredondam_em_4_casas_e_ignoram_divisao_por_zero(): void
    {
        $this->assertSame(0.2222, Metricas::conversao(2, 9));
        $this->assertSame(0.06, Metricas::frequencia(6, 100));
        $this->assertSame(0.1, Metricas::taxaNegativa(1, 10));
        $this->assertNull(Metricas::sinistralidade(100, 0));
    }

    public function test_premio_ganho_e_proporcional_aos_dias_no_periodo(): void
    {
        // viagem de 10 dias, 4 deles dentro do período: ganha 40% do prêmio
        $this->assertSame(12948, Metricas::premioGanho(32370, 4, 10));
        $this->assertSame(0.6, Metricas::sinistralidade(6000, 10000));
    }

    public function test_roi(): void
    {
        $this->assertSame(1.5, Metricas::roi(250000, 100000));
        $this->assertSame(-0.5, Metricas::roi(50000, 100000));
        $this->assertNull(Metricas::roi(50000, 0));
    }

    public function test_nps_e_promotores_menos_detratores(): void
    {
        // 6 promotores, 2 neutros e 2 detratores em 10 respostas: 60% - 20% = 40
        $this->assertSame(40, Metricas::nps(6, 2, 10));
        $this->assertSame(-100, Metricas::nps(0, 5, 5));
        $this->assertNull(Metricas::nps(0, 0, 0));
    }
}
