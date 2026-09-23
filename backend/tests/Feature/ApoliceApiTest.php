<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Container;
use App\Http\Kernel;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Migrator;
use PHPUnit\Framework\TestCase;

final class ApoliceApiTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $pdo = ConnectionFactory::sqlite(':memory:');
        (new Migrator($pdo, __DIR__ . '/../../database/schema'))->migrar();

        $this->kernel = (new Container($pdo))->kernel();
    }

    public function testFluxoCompletoDeCrud(): void
    {
        $criada = $this->request('POST', '/api/apolices', $this->payload());
        $this->assertSame(201, $criada->status);
        $id = $criada->body['id'];
        $this->assertMatchesRegularExpression('/^CRS-\d{4}-[A-F0-9]{8}$/', $criada->body['numero']);
        $this->assertSame(32_370, $criada->body['valorPremioCentavos']);
        $this->assertSame('ativa', $criada->body['status']);

        $lista = $this->request('GET', '/api/apolices');
        $this->assertCount(1, $lista->body);

        $atualizada = $this->request('PUT', "/api/apolices/{$id}", [...$this->payload(), 'plano' => 'premium']);
        $this->assertSame(200, $atualizada->status);
        $this->assertSame('premium', $atualizada->body['plano']);
        $this->assertSame(51_870, $atualizada->body['valorPremioCentavos']);
        $this->assertNotNull($atualizada->body['atualizadoEm']);

        $this->assertSame(204, $this->request('DELETE', "/api/apolices/{$id}")->status);
        $this->assertSame(404, $this->request('GET', "/api/apolices/{$id}")->status);
    }

    public function testFiltraPorBuscaEStatus(): void
    {
        $this->request('POST', '/api/apolices', $this->payload());
        $outra = $this->request('POST', '/api/apolices', [
            ...$this->payload(),
            'seguradoNome' => 'Mariana Costa',
            'seguradoCpf' => '111.444.777-35',
        ]);
        $this->request('PUT', "/api/apolices/{$outra->body['id']}", [
            ...$this->payload(),
            'seguradoNome' => 'Mariana Costa',
            'seguradoCpf' => '111.444.777-35',
            'status' => 'cancelada',
        ]);

        $this->assertCount(1, $this->request('GET', '/api/apolices', query: ['busca' => 'mariana'])->body);
        $this->assertCount(1, $this->request('GET', '/api/apolices', query: ['busca' => '529.982'])->body);
        $this->assertCount(1, $this->request('GET', '/api/apolices', query: ['status' => 'cancelada'])->body);
        $this->assertCount(2, $this->request('GET', '/api/apolices')->body);
    }

    public function testRetorna422ComErrosDeValidacao(): void
    {
        $resposta = $this->request('POST', '/api/apolices', [...$this->payload(), 'seguradoCpf' => '123']);

        $this->assertSame(422, $resposta->status);
        $this->assertArrayHasKey('seguradoCpf', $resposta->body['errors']);
    }

    public function testCotacaoNaoPersisteApolice(): void
    {
        $cotacao = $this->request('POST', '/api/apolices/cotacao', $this->payload());

        $this->assertSame(['valorPremioCentavos' => 32_370, 'dias' => 10], $cotacao->body);
        $this->assertCount(0, $this->request('GET', '/api/apolices')->body);
    }

    public function testRotaInexistenteRetorna404(): void
    {
        $this->assertSame(404, $this->request('GET', '/api/inexistente')->status);
        $this->assertSame(405, $this->request('PATCH', '/api/apolices')->status);
    }

    public function testPreflightCorsRetornaCabecalhos(): void
    {
        $resposta = $this->request('OPTIONS', '/api/apolices');

        $this->assertSame(204, $resposta->status);
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $resposta->headers());
    }

    private function request(string $method, string $path, ?array $body = null, array $query = []): Response
    {
        $request = new Request($method, $path, $query, $body === null ? '' : json_encode($body));

        return $this->kernel->handle($request);
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
