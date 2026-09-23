<?php

declare(strict_types=1);

namespace App\Application\Apolice;

use App\Application\Premio\CalculadoraPremio;
use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\ApoliceRepository;
use App\Domain\Apolice\Endosso;
use App\Domain\Apolice\EndossoRepository;
use App\Domain\Apolice\FiltroApolices;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Segurado\Segurado;
use App\Domain\Segurado\SeguradoRepository;
use App\Domain\Shared\Dinheiro;
use App\Domain\Shared\Pagina;
use App\Domain\Shared\Relogio;
use App\Domain\Shared\Transacao;

final class ApoliceService
{
    private const TENTATIVAS_NUMERO = 5;

    public function __construct(
        private readonly ApoliceRepository $apolices,
        private readonly SeguradoRepository $segurados,
        private readonly EndossoRepository $endossos,
        private readonly Transacao $transacao,
        private readonly ApoliceValidator $validator,
        private readonly CalculadoraPremio $calculadora,
        private readonly GeradorNumeroApolice $geradorNumero,
        private readonly Relogio $relogio,
    ) {
    }

    /** @return Pagina<Apolice> */
    public function listar(FiltroApolices $filtro): Pagina
    {
        return $this->apolices->listar($filtro);
    }

    /** @return array{total: int, ativas: int, premioAtivasCentavos: int} */
    public function resumo(): array
    {
        return $this->apolices->resumo();
    }

    public function buscar(int $id): Apolice
    {
        return $this->apolices->buscarPorId($id) ?? throw NotFoundException::apolice($id);
    }

    /** @return Endosso[] */
    public function endossos(int $apoliceId): array
    {
        $this->buscar($apoliceId);

        return $this->endossos->listarPorApolice($apoliceId);
    }

    public function criar(array $dados): Apolice
    {
        $input = $this->input($dados);

        return $this->transacao->executar(function () use ($input) {
            $segurado = $this->segurados->buscarPorCpf($input->seguradoCpf);

            if ($segurado === null) {
                $segurado = Segurado::novo(
                    $input->seguradoNome,
                    $input->seguradoCpf,
                    $input->seguradoEmail,
                    $input->seguradoNascimento,
                    $this->relogio->hoje(),
                );
            } else {
                $segurado->atualizarDados($input->seguradoNome, $input->seguradoEmail, $input->seguradoNascimento, $this->relogio->hoje());
            }

            $apolice = Apolice::emitir(
                numero: $this->numeroDisponivel(),
                segurado: $segurado,
                destino: $input->destino,
                plano: $input->plano,
                vigencia: $input->vigencia,
                valorPremio: $this->premio($input, $segurado),
                agora: $this->relogio->agora(),
            );

            $this->segurados->salvar($segurado);
            $this->apolices->salvar($apolice);

            return $apolice;
        });
    }

    public function atualizar(int $id, array $dados, string $usuario): Apolice
    {
        $input = $this->input($dados);
        $apolice = $this->buscar($id);
        $segurado = $apolice->segurado();

        if ($segurado->cpf()->numero !== $input->seguradoCpf->numero) {
            throw new DomainException('O CPF do segurado não pode ser alterado. Emita uma nova apólice para outro segurado.', 'seguradoCpf');
        }

        return $this->transacao->executar(function () use ($apolice, $segurado, $input, $usuario) {
            $alteracoesSegurado = $segurado->atualizarDados(
                $input->seguradoNome,
                $input->seguradoEmail,
                $input->seguradoNascimento,
                $this->relogio->hoje(),
            );

            $endosso = $apolice->endossar(
                destino: $input->destino,
                plano: $input->plano,
                vigencia: $input->vigencia,
                valorPremio: $this->premio($input, $segurado),
                status: $input->status ?? $apolice->status(),
                alteracoesSegurado: $alteracoesSegurado,
                usuario: $usuario,
                agora: $this->relogio->agora(),
            );

            $this->segurados->salvar($segurado);
            $this->apolices->salvar($apolice);
            $this->endossos->salvar($endosso);

            return $apolice;
        });
    }

    public function excluir(int $id): void
    {
        $apolice = $this->buscar($id);
        $apolice->excluir($this->relogio->agora());

        $this->apolices->salvar($apolice);
    }

    /** @return array{valorPremioCentavos: int, dias: int} */
    public function cotar(array $dados): array
    {
        $input = $this->input($dados);
        $segurado = Segurado::novo(
            $input->seguradoNome,
            $input->seguradoCpf,
            $input->seguradoEmail,
            $input->seguradoNascimento,
            $this->relogio->hoje(),
        );

        return [
            'valorPremioCentavos' => $this->premio($input, $segurado)->centavos,
            'dias' => $input->vigencia->dias(),
        ];
    }

    private function input(array $dados): ApoliceInput
    {
        $this->validator->validar($dados);

        return ApoliceInput::fromArray($dados);
    }

    private function premio(ApoliceInput $input, Segurado $segurado): Dinheiro
    {
        return $this->calculadora->calcular($input->plano, $input->destino, $input->vigencia, $segurado);
    }

    private function numeroDisponivel(): string
    {
        for ($tentativa = 0; $tentativa < self::TENTATIVAS_NUMERO; $tentativa++) {
            $numero = $this->geradorNumero->gerar();

            if (!$this->apolices->numeroExiste($numero)) {
                return $numero;
            }
        }

        throw new \RuntimeException('Não foi possível gerar um número de apólice disponível.');
    }
}
