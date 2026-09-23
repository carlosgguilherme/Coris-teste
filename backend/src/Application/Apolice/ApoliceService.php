<?php

declare(strict_types=1);

namespace App\Application\Apolice;

use App\Application\Premio\CalculadoraPremio;
use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\ApoliceRepository;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Exception\NotFoundException;
use App\Domain\Shared\Dinheiro;

final class ApoliceService
{
    public function __construct(
        private readonly ApoliceRepository $repository,
        private readonly ApoliceValidator $validator,
        private readonly CalculadoraPremio $calculadora,
        private readonly GeradorNumeroApolice $geradorNumero,
    ) {
    }

    /** @return Apolice[] */
    public function listar(?string $busca = null, ?string $status = null): array
    {
        return $this->repository->listar(
            $busca !== null && trim($busca) !== '' ? trim($busca) : null,
            StatusApolice::tryFrom((string) $status),
        );
    }

    public function buscar(int $id): Apolice
    {
        return $this->repository->buscarPorId($id) ?? throw NotFoundException::apolice($id);
    }

    public function criar(array $dados): Apolice
    {
        $input = $this->input($dados);

        $apolice = Apolice::emitir(
            numero: $this->geradorNumero->gerar(),
            segurado: $input->segurado,
            destino: $input->destino,
            plano: $input->plano,
            vigencia: $input->vigencia,
            valorPremio: $this->premio($input),
        );

        $this->repository->salvar($apolice);

        return $apolice;
    }

    public function atualizar(int $id, array $dados): Apolice
    {
        $apolice = $this->buscar($id);
        $input = $this->input($dados);

        $apolice->atualizar(
            segurado: $input->segurado,
            destino: $input->destino,
            plano: $input->plano,
            vigencia: $input->vigencia,
            valorPremio: $this->premio($input),
            status: $input->status ?? $apolice->status(),
        );

        $this->repository->salvar($apolice);

        return $apolice;
    }

    public function excluir(int $id): void
    {
        $this->buscar($id);
        $this->repository->excluir($id);
    }

    /** @return array{valorPremioCentavos: int, dias: int} */
    public function cotar(array $dados): array
    {
        $input = $this->input($dados);

        return [
            'valorPremioCentavos' => $this->premio($input)->centavos,
            'dias' => $input->vigencia->dias(),
        ];
    }

    private function input(array $dados): ApoliceInput
    {
        $this->validator->validar($dados);

        return ApoliceInput::fromArray($dados);
    }

    private function premio(ApoliceInput $input): Dinheiro
    {
        return $this->calculadora->calcular($input->plano, $input->destino, $input->vigencia, $input->segurado);
    }
}
