<?php

namespace Tests\Feature;

use App\Models\Apolice;
use App\Models\Atendimento;
use App\Models\Campanha;
use App\Models\Canal;
use App\Models\Cotacao;
use App\Models\Segurado;
use App\Models\Sinistro;
use Database\Seeders\CanaisSeeder;
use Database\Seeders\DashboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 10:00:00');
        $this->seed(CanaisSeeder::class);
    }

    public function test_visao_geral_calcula_os_kpis_e_compara_com_o_periodo_anterior(): void
    {
        $apolice = $this->apolice(premio: 40000, emitidaEm: '2026-09-10');
        $this->apolice(premio: 20000, emitidaEm: '2026-08-01');
        $this->apolice(premio: 99999, emitidaEm: '2026-09-11', status: 'cancelada');
        $this->cotacao('convertida', $apolice);
        $this->cotacao('abandonada');
        $this->atendimento(nota: 10);
        $this->atendimento(nota: 3);

        $resposta = $this->getJson('/api/dashboard/visao-geral?periodo=30d')->assertOk();

        $resposta->assertJsonPath('kpis.premioEmitidoCentavos', ['valor' => 40000, 'anterior' => 20000])
            ->assertJsonPath('kpis.apolices.valor', 1)
            ->assertJsonPath('kpis.ticketMedioCentavos.valor', 40000)
            ->assertJsonPath('kpis.conversao.valor', 0.5)
            ->assertJsonPath('kpis.nps.valor', 0)
            ->assertJsonCount(12, 'premioMensal')
            ->assertJsonPath('premioMensal.11', ['mes' => '2026-09', 'atualCentavos' => 40000, 'anoAnteriorCentavos' => 0]);
    }

    public function test_funil_conta_cada_cotacao_nas_etapas_por_onde_passou(): void
    {
        $this->cotacao('convertida', $this->apolice(premio: 30000, emitidaEm: '2026-09-15'));
        $this->cotacao('abandonada', etapaAbandono: 'calculada');

        $this->getJson('/api/dashboard/marketing?periodo=30d')
            ->assertOk()
            ->assertJsonPath('funil.0.total', 2)
            ->assertJsonPath('funil.1.total', 2)
            ->assertJsonPath('funil.2.total', 1)
            ->assertJsonPath('funil.4', ['etapa' => 'convertida', 'label' => 'Apólice emitida', 'total' => 1]);
    }

    public function test_roi_da_campanha(): void
    {
        $campanha = Campanha::create([
            'nome' => 'Férias de Julho', 'utm_source' => 'meta', 'utm_campaign' => 'ferias',
            'orcamento_centavos' => 100000, 'investimento_centavos' => 100000, 'inicio' => '2026-09-01', 'fim' => '2026-09-30',
        ]);
        $apolice = $this->apolice(premio: 250000, emitidaEm: '2026-09-12', campanha: $campanha);
        $this->cotacao('convertida', $apolice, $campanha);

        $this->getJson('/api/dashboard/marketing?periodo=30d')
            ->assertJsonPath('campanhas.0.nome', 'Férias de Julho')
            ->assertJsonPath('campanhas.0.conversao', 1)
            ->assertJsonPath('campanhas.0.roi', 1.5);
    }

    public function test_comercial_agrupa_por_canal_e_plano(): void
    {
        $this->apolice(premio: 30000, emitidaEm: '2026-09-10', canal: 'agencia');
        $this->apolice(premio: 50000, emitidaEm: '2026-09-11', canal: 'agencia');

        $this->getJson('/api/dashboard/comercial?periodo=30d')
            ->assertJsonPath('canais.0', ['canal' => 'Agências de viagem', 'apolices' => 2, 'premioCentavos' => 80000, 'ticketMedioCentavos' => 40000])
            ->assertJsonPath('planos.0.plano', 'Plus');
    }

    public function test_sinistralidade_usa_o_custo_dos_sinistros_sobre_o_premio_ganho(): void
    {
        // viagem de 10 dias inteira dentro do período: prêmio ganho = prêmio todo
        $apolice = $this->apolice(premio: 100000, emitidaEm: '2026-08-25', inicio: '2026-09-01', fim: '2026-09-10');
        $this->sinistro($apolice, 'pago', reclamado: 70000, pago: 60000);
        $this->sinistro($apolice, 'negado', reclamado: 90000);
        $this->sinistro($apolice, 'aberto', reclamado: 20000);

        $this->getJson('/api/dashboard/sinistros?periodo=30d')
            ->assertOk()
            ->assertJsonPath('kpis.sinistros', 3)
            ->assertJsonPath('kpis.sinistralidade', 0.8)
            ->assertJsonPath('kpis.severidadeCentavos', 26666)
            ->assertJsonPath('kpis.taxaNegativa', 0.5)
            ->assertJsonPath('porCobertura.0.custoCentavos', 80000);
    }

    public function test_periodo_invalido(): void
    {
        $this->getJson('/api/dashboard/visao-geral?periodo=5anos')
            ->assertUnprocessable()
            ->assertJsonPath('errors.periodo.0', 'Período inválido.');

        $this->getJson('/api/dashboard/outra-visao')->assertNotFound();
    }

    public function test_seeder_de_demonstracao_gera_dados_para_todas_as_visoes(): void
    {
        $this->seed(DashboardSeeder::class);

        $this->assertGreaterThan(2000, Apolice::count());
        $this->assertGreaterThan(Apolice::count() * 3, Cotacao::count());
        $this->assertGreaterThan(0, Sinistro::count());

        foreach (['visao-geral', 'marketing', 'comercial', 'sinistros'] as $visao) {
            $this->getJson("/api/dashboard/{$visao}?periodo=12m")->assertOk();
        }
    }

    public function test_seeder_pode_rodar_de_novo_sem_duplicar_os_dados(): void
    {
        $this->seed(DashboardSeeder::class);
        $apolices = Apolice::count();

        $this->seed(DashboardSeeder::class);

        $this->assertSame($apolices, Apolice::count());
    }

    private function apolice(int $premio, string $emitidaEm, string $status = 'ativa', string $canal = 'site', ?Campanha $campanha = null, string $inicio = '2026-10-01', string $fim = '2026-10-10'): Apolice
    {
        $apolice = Apolice::create([
            'numero' => 'CRS-'.Str::upper(Str::random(8)),
            'segurado_id' => Segurado::firstOrCreate(
                ['cpf' => '52998224725'],
                ['nome' => 'Carlos Pereira', 'email' => 'carlos@email.com', 'data_nascimento' => '1995-05-10'],
            )->id,
            'canal_id' => Canal::where('codigo', $canal)->value('id'),
            'campanha_id' => $campanha?->id,
            'destino' => 'europa',
            'plano' => 'plus',
            'inicio_vigencia' => $inicio,
            'fim_vigencia' => $fim,
            'valor_premio_centavos' => $premio,
            'status' => $status,
        ]);
        $apolice->forceFill(['created_at' => $emitidaEm])->save();

        return $apolice;
    }

    private function cotacao(string $status, ?Apolice $apolice = null, ?Campanha $campanha = null, string $etapaAbandono = 'iniciada'): Cotacao
    {
        $cotacao = Cotacao::create([
            'codigo' => (string) Str::uuid(),
            'canal_id' => Canal::where('codigo', 'site')->value('id'),
            'campanha_id' => $campanha?->id,
            'apolice_id' => $apolice?->id,
            'destino' => 'europa',
            'plano' => 'plus',
            'dias' => 10,
            'valor_calculado_centavos' => 32370,
            'device' => 'desktop',
            'status' => $status,
            'etapa_abandono' => $status === 'abandonada' ? $etapaAbandono : null,
        ]);

        $etapas = ['iniciada', 'calculada', 'dados_preenchidos', 'pagamento', 'convertida'];
        $ultima = $status === 'convertida' ? 'convertida' : $etapaAbandono;
        foreach (array_slice($etapas, 0, array_search($ultima, $etapas) + 1) as $etapa) {
            $cotacao->eventos()->create(['etapa' => $etapa, 'device' => 'desktop', 'ocorrido_em' => now()]);
        }

        return $cotacao;
    }

    private function sinistro(Apolice $apolice, string $status, int $reclamado, int $pago = 0): void
    {
        Sinistro::create([
            'numero' => 'SIN-'.Str::upper(Str::random(8)),
            'apolice_id' => $apolice->id,
            'cobertura' => 'despesas_medicas',
            'data_ocorrencia' => '2026-09-05',
            'data_aviso' => '2026-09-06',
            'valor_reclamado_centavos' => $reclamado,
            'valor_pago_centavos' => $pago,
            'status' => $status,
        ]);
    }

    private function atendimento(int $nota): void
    {
        Atendimento::create([
            'canal' => 'whatsapp', 'tipo' => 'Orientação médica', 'inicio' => '2026-09-15 14:00:00',
            'tempo_espera_seg' => 60, 'dentro_sla' => true, 'nps' => $nota,
        ]);
    }
}
