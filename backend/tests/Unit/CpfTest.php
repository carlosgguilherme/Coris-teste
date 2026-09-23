<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfTest extends TestCase
{
    #[DataProvider('cpfsValidos')]
    public function test_aceita_cpf_valido_com_ou_sem_mascara(string $cpf): void
    {
        $this->assertTrue(Cpf::valido($cpf));
    }

    #[DataProvider('cpfsInvalidos')]
    public function test_rejeita_cpf_invalido(string $cpf): void
    {
        $this->assertFalse(Cpf::valido($cpf));
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
