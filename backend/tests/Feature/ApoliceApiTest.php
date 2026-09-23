<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Container;
use App\Domain\Usuario\Usuario;
use App\Http\Kernel;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Migrator;
use PHPUnit\Framework\TestCase;
use Tests\Support\RelogioFixo;

final class ApoliceApiTest extends TestCase
{
    private Kernel $kernel;
    private string $token;

    protected function setUp(): void
    {
        $pdo = ConnectionFactory::sqlite(':memory:');
        (new Migrator($pdo, __DIR__ . '/../../database/schema'))->migrar();

        $container = new Container($pdo, new RelogioFixo());
        $container->usuarios()->salvar(Usuario::cadastrar('Operador', 'operador@email.com', 'senha-forte-123'));

        $this->kernel = $container->kernel();
        $this->token = $this->request('POST', '/api/auth/login', ['email' => 'operador@email.com', 'senha' => 'senha-forte-123'])->body['token'];
    }

    public function testRotasProtegidasExigemToken(): void
    {
        $semToken = $this->kernel->handle(new Request('GET', '/api/apolices'));

        $this->assertSame(401, $semToken->status);
        $this->assertSame(200, $this->request('GET', '/api/health')->status);
        $this->assertSame(['nome' => 'Operador', 'email' => 'operador@email.com'], $this->request('GET', '/api/auth/eu')->body);
    }

    public function testLoginComSenhaErradaRetorna401(): void
    {
        $resposta = $this->request('POST', '/api/auth/login', ['email' => 'operador@email.com', 'senha' => 'errada']);

        $this->assertSame(401, $resposta->status);
    }

    public function testFluxoCompletoComEndossoEExclusaoLogica(): void
    {
        $criada = $this->request('POST', '/api/apolices', $this->payload());
        $this->assertSame(201, $criada->status);
        $this->assertMatchesRegularExpression('/^CRS-\d{4}-[A-F0-9]{8}$/', $criada->body['numero']);
        $this->assertSame(32_370, $criada->body['valorPremioCentavos']);
        $id = $criada->body['id'];

        $atualizada = $this->request('PUT', "/api/apolices/{$id}", [...$this->payload(), 'plano' => 'premium']);
        $this->assertSame(200, $atualizada->status);
        $this->assertSame(51_870, $atualizada->body['valorPremioCentavos']);

        $endossos = $this->request('GET', "/api/apolices/{$id}/endossos")->body;
        $this->assertCount(1, $endossos);
        $this->assertSame(1, $endossos[0]['numero']);
        $this->assertSame(19_500, $endossos[0]['diferencaCentavos']);
        $this->assertSame('operador@email.com', $endossos[0]['usuario']);
        $this->assertContains('Plano: Plus → Premium', $endossos[0]['alteracoes']);

        $this->assertSame(204, $this->request('DELETE', "/api/apolices/{$id}")->status);
        $this->assertSame(404, $this->request('GET', "/api/apolices/{$id}")->status);
        $this->assertSame(0, $this->request('GET', '/api/apolices')->body['paginacao']['total']);
    }

    public function testSeguradoEReaproveitadoPeloCpf(): void
    {
        $primeira = $this->request('POST', '/api/apolices', $this->payload());
        $segunda = $this->request('POST', '/api/apolices', [...$this->payload(), 'destino' => 'asia']);

        $this->assertSame($primeira->body['seguradoId'], $segunda->body['seguradoId']);
    }

    public function testCpfNaoPodeSerAlteradoNoEndosso(): void
    {
        $id = $this->request('POST', '/api/apolices', $this->payload())->body['id'];

        $resposta = $this->request('PUT', "/api/apolices/{$id}", [...$this->payload(), 'seguradoCpf' => '111.444.777-35']);

        $this->assertSame(422, $resposta->status);
        $this->assertArrayHasKey('seguradoCpf', $resposta->body['errors']);
    }

    public function testRegraDeNegocioRetornaErroNoCampo(): void
    {
        $passado = $this->request('POST', '/api/apolices', [...$this->payload(), 'inicioVigencia' => '2026-09-01']);
        $invertida = $this->request('POST', '/api/apolices', [...$this->payload(), 'fimVigencia' => '2026-09-25']);

        $this->assertSame(['inicioVigencia' => 'O início da vigência não pode ser anterior a hoje.'], $passado->body['errors']);
        $this->assertArrayHasKey('fimVigencia', $invertida->body['errors']);
    }

    public function testListagemPaginadaComFiltrosEResumo(): void
    {
        foreach (['529.982.247-25', '111.444.777-35', '390.533.447-05'] as $indice => $cpf) {
            $this->request('POST', '/api/apolices', [...$this->payload(), 'seguradoCpf' => $cpf, 'seguradoNome' => "Segurado {$indice}"]);
        }

        $pagina = $this->request('GET', '/api/apolices', query: ['porPagina' => '2', 'pagina' => '2'])->body;
        $this->assertCount(1, $pagina['dados']);
        $this->assertSame(['pagina' => 2, 'porPagina' => 2, 'total' => 3, 'totalPaginas' => 2], $pagina['paginacao']);

        $this->assertSame(1, $this->request('GET', '/api/apolices', query: ['busca' => '390.533'])->body['paginacao']['total']);
        $this->assertSame(0, $this->request('GET', '/api/apolices', query: ['status' => 'cancelada'])->body['paginacao']['total']);

        $this->assertSame(
            ['total' => 3, 'ativas' => 3, 'premioAtivasCentavos' => 97_110],
            $this->request('GET', '/api/apolices/resumo')->body,
        );
    }

    public function testCotacaoNaoPersisteApolice(): void
    {
        $cotacao = $this->request('POST', '/api/apolices/cotacao', $this->payload());

        $this->assertSame(['valorPremioCentavos' => 32_370, 'dias' => 10], $cotacao->body);
        $this->assertSame(0, $this->request('GET', '/api/apolices/resumo')->body['total']);
    }

    public function testRotaInexistenteEMetodoNaoPermitido(): void
    {
        $this->assertSame(404, $this->request('GET', '/api/inexistente')->status);
        $this->assertSame(405, $this->request('PATCH', '/api/apolices')->status);
    }

    public function testPreflightCorsRetornaCabecalhos(): void
    {
        $resposta = $this->kernel->handle(new Request('OPTIONS', '/api/apolices'));

        $this->assertSame(204, $resposta->status);
        $this->assertStringContainsString('Authorization', $resposta->headers()['Access-Control-Allow-Headers']);
    }

    private function request(string $method, string $path, ?array $body = null, array $query = []): Response
    {
        $headers = isset($this->token) ? ['authorization' => "Bearer {$this->token}"] : [];

        return $this->kernel->handle(new Request($method, $path, $query, $body === null ? '' : json_encode($body), $headers));
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
