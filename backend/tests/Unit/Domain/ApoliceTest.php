<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Exception\DomainException;
use App\Domain\Shared\Dinheiro;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fabrica;

final class ApoliceTest extends TestCase
{
    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $this->agora = new DateTimeImmutable('2026-09-20 10:00:00');
    }

    public function testEmiteApoliceAtiva(): void
    {
        $apolice = Fabrica::apolice();

        $this->assertSame(StatusApolice::Ativa, $apolice->status());
        $this->assertSame('CRS-2026-TESTE', $apolice->numero());
        $this->assertNull($apolice->excluidoEm());
    }

    public function testNaoEmiteComVigenciaIniciandoNoPassado(): void
    {
        $this->expectExceptionObject(new DomainException('O início da vigência não pode ser anterior a hoje.', 'inicioVigencia'));

        Apolice::emitir('CRS-1', Fabrica::segurado(), Destino::Europa, Plano::Plus, Fabrica::vigencia('2026-09-19', '2026-09-25'), Dinheiro::centavos(100), $this->agora);
    }

    public function testPermiteEmitirComInicioHoje(): void
    {
        $apolice = Apolice::emitir('CRS-1', Fabrica::segurado(), Destino::Europa, Plano::Plus, Fabrica::vigencia('2026-09-20', '2026-09-25'), Dinheiro::centavos(100), $this->agora);

        $this->assertSame(6, $apolice->vigencia()->dias());
    }

    public function testNaoPermitePremioZerado(): void
    {
        $this->expectException(DomainException::class);

        Fabrica::apolice(premioCentavos: 0);
    }

    public function testEndossoRegistraAlteracoesEDiferencaDePremio(): void
    {
        $apolice = Fabrica::apolice();

        $endosso = $apolice->endossar(
            Destino::Europa,
            Plano::Premium,
            Fabrica::vigencia('2026-10-01', '2026-10-12'),
            Dinheiro::centavos(62_244),
            StatusApolice::Ativa,
            ['Nome do segurado: Carlos → Carlos Pereira'],
            'operador@email.com',
            $this->agora,
        );

        $this->assertSame([
            'Nome do segurado: Carlos → Carlos Pereira',
            'Plano: Plus → Premium',
            'Vigência: 01/10/2026 a 10/10/2026 → 01/10/2026 a 12/10/2026',
            'Prêmio: R$ 323,70 → R$ 622,44',
        ], $endosso->alteracoes);
        $this->assertSame(29_874, $endosso->diferencaEmCentavos());
        $this->assertSame('operador@email.com', $endosso->usuario);
        $this->assertSame(Plano::Premium, $apolice->plano());
    }

    public function testEndossoSemAlteracaoNaoEPermitido(): void
    {
        $apolice = Fabrica::apolice();

        $this->expectExceptionMessage('Nenhuma alteração foi feita na apólice.');

        $apolice->endossar(Destino::Europa, Plano::Plus, Fabrica::vigencia(), Dinheiro::centavos(32_370), StatusApolice::Ativa, [], 'op', $this->agora);
    }

    public function testEndossoNaoPodeMoverInicioParaOPassado(): void
    {
        $apolice = Fabrica::apolice();

        $this->expectExceptionMessage('O início da vigência não pode ser anterior a hoje.');

        $apolice->endossar(Destino::Europa, Plano::Plus, Fabrica::vigencia('2026-09-10', '2026-10-10'), Dinheiro::centavos(32_370), StatusApolice::Ativa, [], 'op', $this->agora);
    }

    public function testApoliceCanceladaSoPodeSerReativada(): void
    {
        $apolice = Fabrica::apolice();
        $apolice->endossar(Destino::Europa, Plano::Plus, Fabrica::vigencia(), Dinheiro::centavos(32_370), StatusApolice::Cancelada, [], 'op', $this->agora);

        try {
            $apolice->endossar(Destino::Asia, Plano::Plus, Fabrica::vigencia(), Dinheiro::centavos(32_370), StatusApolice::Cancelada, [], 'op', $this->agora);
            $this->fail('Apólice cancelada não deveria ser alterada.');
        } catch (DomainException $e) {
            $this->assertSame('status', $e->campo);
        }

        $endosso = $apolice->endossar(Destino::Europa, Plano::Plus, Fabrica::vigencia(), Dinheiro::centavos(32_370), StatusApolice::Ativa, [], 'op', $this->agora);
        $this->assertSame(['Status: Cancelada → Ativa'], $endosso->alteracoes);
    }

    public function testExclusaoLogica(): void
    {
        $apolice = Fabrica::apolice();
        $apolice->excluir($this->agora);

        $this->assertEquals($this->agora, $apolice->excluidoEm());

        $this->expectException(DomainException::class);
        $apolice->excluir($this->agora);
    }

    public function testVigenciaInvalidaInformaOCampo(): void
    {
        try {
            Fabrica::vigencia('2026-10-10', '2026-10-01');
            $this->fail('Vigência invertida deveria falhar.');
        } catch (DomainException $e) {
            $this->assertSame('fimVigencia', $e->campo);
        }

        $this->expectExceptionMessage('A vigência máxima é de 365 dias.');
        Fabrica::vigencia('2026-01-01', '2027-01-01');
    }
}
