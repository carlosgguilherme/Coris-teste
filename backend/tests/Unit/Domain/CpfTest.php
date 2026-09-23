<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\DomainException;
use App\Domain\Shared\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CpfTest extends TestCase
{
    #[DataProvider('cpfsValidos')]
    public function testAceitaCpfValidoComOuSemMascara(string $cpf): void
    {
        $this->assertTrue(Cpf::isValid($cpf));
    }

    #[DataProvider('cpfsInvalidos')]
    public function testRejeitaCpfInvalido(string $cpf): void
    {
        $this->assertFalse(Cpf::isValid($cpf));
    }

    public function testArmazenaSomenteDigitosEFormata(): void
    {
        $cpf = Cpf::from('529.982.247-25');

        $this->assertSame('52998224725', $cpf->numero);
        $this->assertSame('529.982.247-25', $cpf->formatado());
    }

    public function testLancaExcecaoAoCriarCpfInvalido(): void
    {
        $this->expectException(DomainException::class);

        Cpf::from('123.456.789-00');
    }

    public static function cpfsValidos(): array
    {
        return [['529.982.247-25'], ['52998224725'], ['111.444.777-35']];
    }

    public static function cpfsInvalidos(): array
    {
        return [[''], ['111.111.111-11'], ['123.456.789-00'], ['5299822472']];
    }
}
