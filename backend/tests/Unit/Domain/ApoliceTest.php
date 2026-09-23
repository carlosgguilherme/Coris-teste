<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Segurado;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Apolice\Vigencia;
use App\Domain\Exception\DomainException;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ApoliceTest extends TestCase
{
    public function testEmiteApoliceAtivaSemId(): void
    {
        $apolice = $this->emitir();

        $this->assertNull($apolice->id());
        $this->assertSame(StatusApolice::Ativa, $apolice->status());
        $this->assertSame('CRS-2026-TESTE', $apolice->numero());
    }

    public function testNaoPermitePremioZerado(): void
    {
        $this->expectException(DomainException::class);

        $this->emitir(premio: 0);
    }

    public function testVigenciaContaOsDiasInclusive(): void
    {
        $vigencia = new Vigencia(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-10'));

        $this->assertSame(10, $vigencia->dias());
    }

    public function testVigenciaNaoPodeTerminarAntesDeComecar(): void
    {
        $this->expectException(DomainException::class);

        new Vigencia(new DateTimeImmutable('2026-10-10'), new DateTimeImmutable('2026-10-01'));
    }

    public function testVigenciaNaoPodeUltrapassarUmAno(): void
    {
        $this->expectException(DomainException::class);

        new Vigencia(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2027-01-01'));
    }

    public function testApoliceCanceladaSoPodeSerAlteradaParaReativar(): void
    {
        $apolice = $this->emitir();
        $apolice->atualizar($apolice->segurado(), Destino::Europa, Plano::Plus, $apolice->vigencia(), 100, StatusApolice::Cancelada);

        $apolice->atualizar($apolice->segurado(), Destino::Europa, Plano::Plus, $apolice->vigencia(), 100, StatusApolice::Ativa);
        $this->assertSame(StatusApolice::Ativa, $apolice->status());

        $apolice->atualizar($apolice->segurado(), Destino::Europa, Plano::Plus, $apolice->vigencia(), 100, StatusApolice::Cancelada);
        $this->expectException(DomainException::class);
        $apolice->atualizar($apolice->segurado(), Destino::Asia, Plano::Plus, $apolice->vigencia(), 100, StatusApolice::Cancelada);
    }

    public function testIdSoPodeSerDefinidoUmaVez(): void
    {
        $apolice = $this->emitir();
        $apolice->definirId(10);

        $this->expectException(DomainException::class);
        $apolice->definirId(11);
    }

    private function emitir(float $premio = 150.0): Apolice
    {
        return Apolice::emitir(
            'CRS-2026-TESTE',
            new Segurado('Carlos Pereira', Cpf::from('52998224725'), 'carlos@email.com', new DateTimeImmutable('1995-05-10')),
            Destino::Europa,
            Plano::Plus,
            new Vigencia(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-10')),
            $premio,
        );
    }
}
