<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Apolice\ApoliceValidator;
use App\Application\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class ApoliceValidatorTest extends TestCase
{
    private ApoliceValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ApoliceValidator();
    }

    public function testDadosValidosNaoLancamExcecao(): void
    {
        $this->validator->validar($this->dadosValidos());

        $this->addToAssertionCount(1);
    }

    public function testRetornaErroPorCampo(): void
    {
        $erros = $this->errosPara([
            'seguradoNome' => 'Jo',
            'seguradoCpf' => '000.000.000-00',
            'seguradoEmail' => 'email-invalido',
            'destino' => 'marte',
            'plano' => 'ouro',
        ]);

        $this->assertEqualsCanonicalizing(
            ['seguradoNome', 'seguradoCpf', 'seguradoEmail', 'destino', 'plano'],
            array_keys($erros),
        );
    }

    public function testRejeitaDataEmFormatoErrado(): void
    {
        $erros = $this->errosPara(['seguradoNascimento' => '10/05/1995', 'inicioVigencia' => '2026-02-30']);

        $this->assertArrayHasKey('seguradoNascimento', $erros);
        $this->assertArrayHasKey('inicioVigencia', $erros);
    }

    public function testRegrasDeVigenciaNaoSaoResponsabilidadeDoValidador(): void
    {
        $this->validator->validar([...$this->dadosValidos(), 'inicioVigencia' => '2026-10-10', 'fimVigencia' => '2026-10-01']);

        $this->addToAssertionCount(1);
    }

    public function testIgnoraValoresQueNaoSaoTexto(): void
    {
        $erros = $this->errosPara(['seguradoNome' => ['array'], 'destino' => null]);

        $this->assertSame(['seguradoNome', 'destino'], array_keys($erros));
    }

    public function testRejeitaStatusDesconhecido(): void
    {
        $erros = $this->errosPara(['status' => 'suspensa']);

        $this->assertSame(['status'], array_keys($erros));
    }

    private function errosPara(array $alteracoes): array
    {
        try {
            $this->validator->validar([...$this->dadosValidos(), ...$alteracoes]);
        } catch (ValidationException $e) {
            return $e->errors;
        }

        $this->fail('Era esperada uma ValidationException.');
    }

    private function dadosValidos(): array
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
