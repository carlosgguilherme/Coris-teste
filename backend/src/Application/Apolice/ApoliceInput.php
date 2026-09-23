<?php

declare(strict_types=1);

namespace App\Application\Apolice;

use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;

final class ApoliceInput
{
    private function __construct(
        public readonly string $seguradoNome,
        public readonly Cpf $seguradoCpf,
        public readonly string $seguradoEmail,
        public readonly DateTimeImmutable $seguradoNascimento,
        public readonly Destino $destino,
        public readonly Plano $plano,
        public readonly Vigencia $vigencia,
        public readonly ?StatusApolice $status,
    ) {
    }

    public static function fromArray(array $dados): self
    {
        return new self(
            seguradoNome: (string) $dados['seguradoNome'],
            seguradoCpf: Cpf::from((string) $dados['seguradoCpf']),
            seguradoEmail: (string) $dados['seguradoEmail'],
            seguradoNascimento: new DateTimeImmutable((string) $dados['seguradoNascimento']),
            destino: Destino::from((string) $dados['destino']),
            plano: Plano::from((string) $dados['plano']),
            vigencia: new Vigencia(
                new DateTimeImmutable((string) $dados['inicioVigencia']),
                new DateTimeImmutable((string) $dados['fimVigencia']),
            ),
            status: isset($dados['status']) ? StatusApolice::from((string) $dados['status']) : null,
        );
    }
}
