<?php

declare(strict_types=1);

namespace App\Application\Apolice;

use App\Application\Exception\ValidationException;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;

final class ApoliceValidator
{
    private const FORMATO_DATA = 'Y-m-d';

    /** @throws ValidationException */
    public function validar(array $dados): void
    {
        $erros = [];

        $nome = trim((string) ($dados['seguradoNome'] ?? ''));
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 120) {
            $erros['seguradoNome'] = 'Informe o nome completo (3 a 120 caracteres).';
        }

        if (!Cpf::isValid((string) ($dados['seguradoCpf'] ?? ''))) {
            $erros['seguradoCpf'] = 'CPF inválido.';
        }

        if (!filter_var($dados['seguradoEmail'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $erros['seguradoEmail'] = 'E-mail inválido.';
        }

        $nascimento = $this->data($dados['seguradoNascimento'] ?? null);
        if ($nascimento === null) {
            $erros['seguradoNascimento'] = 'Data de nascimento inválida.';
        } elseif ($nascimento > new DateTimeImmutable('today')) {
            $erros['seguradoNascimento'] = 'A data de nascimento não pode ser futura.';
        }

        if (Destino::tryFrom((string) ($dados['destino'] ?? '')) === null) {
            $erros['destino'] = 'Destino inválido.';
        }

        if (Plano::tryFrom((string) ($dados['plano'] ?? '')) === null) {
            $erros['plano'] = 'Plano inválido.';
        }

        if (isset($dados['status']) && StatusApolice::tryFrom((string) $dados['status']) === null) {
            $erros['status'] = 'Status inválido.';
        }

        $inicio = $this->data($dados['inicioVigencia'] ?? null);
        $fim = $this->data($dados['fimVigencia'] ?? null);

        if ($inicio === null) {
            $erros['inicioVigencia'] = 'Data de início inválida.';
        }

        if ($fim === null) {
            $erros['fimVigencia'] = 'Data de fim inválida.';
        } elseif ($inicio !== null && $fim < $inicio) {
            $erros['fimVigencia'] = 'O fim da vigência deve ser igual ou posterior ao início.';
        } elseif ($inicio !== null && $inicio->diff($fim)->days + 1 > Vigencia::DIAS_MAXIMOS) {
            $erros['fimVigencia'] = sprintf('A vigência máxima é de %d dias.', Vigencia::DIAS_MAXIMOS);
        }

        if ($erros !== []) {
            throw new ValidationException($erros);
        }
    }

    private function data(mixed $valor): ?DateTimeImmutable
    {
        if (!is_string($valor)) {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!' . self::FORMATO_DATA, $valor);

        return $data && $data->format(self::FORMATO_DATA) === $valor ? $data : null;
    }
}
