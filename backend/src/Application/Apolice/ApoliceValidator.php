<?php

declare(strict_types=1);

namespace App\Application\Apolice;

use App\Application\Exception\ValidationException;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;

/**
 * Valida apenas formato e presença dos campos. Regras de negócio
 * (vigência, datas, status) ficam no domínio.
 */
final class ApoliceValidator
{
    private const FORMATO_DATA = 'Y-m-d';

    /** @throws ValidationException */
    public function validar(array $dados): void
    {
        $erros = [];

        $nome = trim($this->texto($dados, 'seguradoNome'));
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 120) {
            $erros['seguradoNome'] = 'Informe o nome completo (3 a 120 caracteres).';
        }

        if (!Cpf::isValid($this->texto($dados, 'seguradoCpf'))) {
            $erros['seguradoCpf'] = 'CPF inválido.';
        }

        if (!filter_var($this->texto($dados, 'seguradoEmail'), FILTER_VALIDATE_EMAIL)) {
            $erros['seguradoEmail'] = 'E-mail inválido.';
        }

        foreach (['seguradoNascimento' => 'Data de nascimento inválida.', 'inicioVigencia' => 'Data de início inválida.', 'fimVigencia' => 'Data de fim inválida.'] as $campo => $mensagem) {
            if ($this->data($this->texto($dados, $campo)) === null) {
                $erros[$campo] = $mensagem;
            }
        }

        if (Destino::tryFrom($this->texto($dados, 'destino')) === null) {
            $erros['destino'] = 'Destino inválido.';
        }

        if (Plano::tryFrom($this->texto($dados, 'plano')) === null) {
            $erros['plano'] = 'Plano inválido.';
        }

        if (isset($dados['status']) && StatusApolice::tryFrom($this->texto($dados, 'status')) === null) {
            $erros['status'] = 'Status inválido.';
        }

        if ($erros !== []) {
            throw new ValidationException($erros);
        }
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_scalar($valor) ? (string) $valor : '';
    }

    private function data(string $valor): ?DateTimeImmutable
    {
        $data = DateTimeImmutable::createFromFormat('!' . self::FORMATO_DATA, $valor);

        return $data && $data->format(self::FORMATO_DATA) === $valor ? $data : null;
    }
}
