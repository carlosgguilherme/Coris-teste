<?php

namespace Tests\Feature;

use App\Models\Apolice;
use App\Models\Segurado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApoliceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 10:00:00');
    }

    public function test_cria_apolice_calculando_o_premio(): void
    {
        $resposta = $this->postJson('/api/apolices', $this->payload());

        $resposta->assertCreated()
            ->assertJsonPath('valorPremioCentavos', 32370)
            ->assertJsonPath('status', 'ativa')
            ->assertJsonPath('seguradoCpf', '529.982.247-25')
            ->assertJsonPath('dias', 10);

        $this->assertMatchesRegularExpression('/^CRS-2026-[A-F0-9]{8}$/', $resposta->json('numero'));
        $this->assertDatabaseHas('segurados', ['cpf' => '52998224725']);
    }

    public function test_reaproveita_o_segurado_pelo_cpf(): void
    {
        $primeira = $this->postJson('/api/apolices', $this->payload());
        $segunda = $this->postJson('/api/apolices', [...$this->payload(), 'destino' => 'asia']);

        $this->assertSame($primeira->json('seguradoId'), $segunda->json('seguradoId'));
        $this->assertSame(1, Segurado::count());
    }

    public function test_lista_com_paginacao_busca_e_filtro(): void
    {
        foreach (['529.982.247-25', '111.444.777-35', '390.533.447-05'] as $indice => $cpf) {
            $this->postJson('/api/apolices', [...$this->payload(), 'seguradoCpf' => $cpf, 'seguradoNome' => "Segurado {$indice}"]);
        }

        $this->getJson('/api/apolices')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.current_page', 1);

        $this->getJson('/api/apolices?busca=390.533')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/apolices?busca=Segurado 1')->assertJsonPath('data.0.seguradoCpf', '111.444.777-35');
        $this->getJson('/api/apolices?status=cancelada')->assertJsonPath('meta.total', 0);
    }

    public function test_edita_e_recalcula_o_premio(): void
    {
        $id = $this->postJson('/api/apolices', $this->payload())->json('id');

        $this->putJson("/api/apolices/{$id}", [...$this->payload(), 'plano' => 'premium'])
            ->assertOk()
            ->assertJsonPath('plano', 'premium')
            ->assertJsonPath('valorPremioCentavos', 51870);
    }

    public function test_apolice_cancelada_so_pode_ser_reativada(): void
    {
        $id = $this->postJson('/api/apolices', $this->payload())->json('id');
        $this->putJson("/api/apolices/{$id}", [...$this->payload(), 'status' => 'cancelada'])->assertOk();

        $this->putJson("/api/apolices/{$id}", [...$this->payload(), 'status' => 'cancelada', 'destino' => 'asia'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->putJson("/api/apolices/{$id}", [...$this->payload(), 'status' => 'ativa'])
            ->assertOk()
            ->assertJsonPath('status', 'ativa');
    }

    public function test_exclusao_logica(): void
    {
        $id = $this->postJson('/api/apolices', $this->payload())->json('id');

        $this->deleteJson("/api/apolices/{$id}")->assertNoContent();

        $this->getJson("/api/apolices/{$id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Apólice não encontrada.');
        $this->assertSoftDeleted('apolices', ['id' => $id]);
        $this->getJson('/api/apolices')->assertJsonPath('meta.total', 0);
    }

    public function test_valida_os_campos_em_portugues(): void
    {
        $this->postJson('/api/apolices', [...$this->payload(), 'seguradoCpf' => '123.456.789-00', 'plano' => 'ouro'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.seguradoCpf.0', 'CPF inválido.')
            ->assertJsonPath('errors.plano.0', 'Plano inválido.');
    }

    public function test_vigencia_nao_pode_comecar_no_passado_nem_passar_de_365_dias(): void
    {
        $this->postJson('/api/apolices', [...$this->payload(), 'inicioVigencia' => '2026-09-19'])
            ->assertJsonPath('errors.inicioVigencia.0', 'O início da vigência não pode ser anterior a hoje.');

        $this->postJson('/api/apolices', [...$this->payload(), 'fimVigencia' => '2027-10-01'])
            ->assertJsonPath('errors.fimVigencia.0', 'A vigência máxima é de 365 dias.');

        $this->postJson('/api/apolices', [...$this->payload(), 'fimVigencia' => '2026-09-30'])
            ->assertJsonValidationErrors('fimVigencia');
    }

    public function test_apolice_ja_em_vigor_pode_ser_editada_sem_mudar_o_inicio(): void
    {
        $id = $this->postJson('/api/apolices', $this->payload())->json('id');
        Carbon::setTestNow('2026-10-05');

        $this->putJson("/api/apolices/{$id}", [...$this->payload(), 'plano' => 'premium'])->assertOk();
    }

    public function test_cotacao_nao_persiste(): void
    {
        $this->postJson('/api/apolices/cotacao', $this->payload())
            ->assertOk()
            ->assertExactJson(['valorPremioCentavos' => 32370, 'dias' => 10]);

        $this->assertSame(0, Apolice::count());
    }

    public function test_resumo_ignora_canceladas_e_excluidas(): void
    {
        $ids = collect(['529.982.247-25', '111.444.777-35', '390.533.447-05'])
            ->map(fn ($cpf) => $this->postJson('/api/apolices', [...$this->payload(), 'seguradoCpf' => $cpf])->json('id'));

        $this->putJson("/api/apolices/{$ids[1]}", [...$this->payload(), 'seguradoCpf' => '111.444.777-35', 'status' => 'cancelada']);
        $this->deleteJson("/api/apolices/{$ids[2]}");

        $this->getJson('/api/apolices/resumo')->assertExactJson([
            'total' => 2,
            'ativas' => 1,
            'premioAtivasCentavos' => 32370,
        ]);
    }

    public function test_opcoes_e_health(): void
    {
        $this->getJson('/api/health')->assertExactJson(['status' => 'ok']);
        $this->getJson('/api/opcoes')
            ->assertJsonCount(3, 'planos')
            ->assertJsonPath('planos.1.valorDiariaCentavos', 2490)
            ->assertJsonCount(7, 'destinos');
    }

    public function test_rota_inexistente(): void
    {
        $this->getJson('/api/inexistente')->assertNotFound()->assertJsonPath('message', 'Rota não encontrada.');
    }

    private function payload(): array
    {
        return [
            'seguradoNome' => 'Carlos Pereira',
            'seguradoCpf' => '529.982.247-25',
            'seguradoEmail' => 'carlos@email.com',
            'seguradoNascimento' => '1995-05-10',
            'destino' => 'europa',
            'plano' => 'plus',
            'inicioVigencia' => '2026-10-01',
            'fimVigencia' => '2026-10-10',
        ];
    }
}
