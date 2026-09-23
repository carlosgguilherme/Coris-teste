<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\DomainException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fabrica;

final class SeguradoTest extends TestCase
{
    public function testNormalizaEmail(): void
    {
        $segurado = Fabrica::segurado();
        $segurado->atualizarDados('Carlos Pereira', '  CARLOS@Email.com ', $segurado->dataNascimento(), new DateTimeImmutable('2026-09-20'));

        $this->assertSame('carlos@email.com', $segurado->email());
    }

    public function testNaoAceitaNascimentoFuturo(): void
    {
        try {
            Fabrica::segurado('2030-01-01');
            $this->fail('Nascimento futuro deveria falhar.');
        } catch (DomainException $e) {
            $this->assertSame('seguradoNascimento', $e->campo);
        }
    }

    public function testDescreveAsAlteracoesDeDados(): void
    {
        $segurado = Fabrica::segurado();
        $segurado->definirId(1);

        $alteracoes = $segurado->atualizarDados('Carlos G. Pereira', 'novo@email.com', new DateTimeImmutable('1995-05-11'), new DateTimeImmutable('2026-09-20'));

        $this->assertSame([
            'Nome do segurado: Carlos Pereira → Carlos G. Pereira',
            'E-mail do segurado: carlos@email.com → novo@email.com',
            'Nascimento do segurado: 10/05/1995 → 11/05/1995',
        ], $alteracoes);
    }

    public function testCalculaIdadeNaData(): void
    {
        $this->assertSame(31, Fabrica::segurado()->idadeEm(new DateTimeImmutable('2026-10-01')));
    }
}
