<?php

declare(strict_types=1);

namespace App\Application\Apolice;

use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Segurado;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;

final class ApoliceInput
{
    private function __construct(
        public readonly Segurado $segurado,
        public readonly Destino $destino,
        public readonly Plano $plano,
        public readonly Vigencia $vigencia,
        public readonly ?StatusApolice $status,
    ) {
    }

    public static function fromArray(array $dados): self
    {
        return new self(
            segurado: new Segurado(
                nome: trim((string) $dados['seguradoNome']),
                cpf: Cpf::from((string) $dados['seguradoCpf']),
                email: (string) $dados['seguradoEmail'],
                dataNascimento: new DateTimeImmutable((string) $dados['seguradoNascimento']),
            ),
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
