<?php

namespace App\Http\Requests;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CotacaoRequest extends FormRequest
{
    public const VIGENCIA_MAXIMA_DIAS = 365;

    public function rules(): array
    {
        return [
            'seguradoNome' => ['required', 'string', 'min:3', 'max:120'],
            'seguradoCpf' => ['required', new Cpf],
            'seguradoEmail' => ['required', 'email', 'max:150'],
            'seguradoNascimento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'destino' => ['required', Rule::in(array_column(Destino::cases(), 'value'))],
            'plano' => ['required', Rule::in(array_column(Plano::cases(), 'value'))],
            'inicioVigencia' => ['required', 'date_format:Y-m-d'],
            'fimVigencia' => ['required', 'date_format:Y-m-d', 'after_or_equal:inicioVigencia'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['inicioVigencia', 'fimVigencia'])) {
                    return;
                }

                $dias = Carbon::parse($this->input('inicioVigencia'))->diffInDays($this->input('fimVigencia')) + 1;

                if ($dias > self::VIGENCIA_MAXIMA_DIAS) {
                    $validator->errors()->add('fimVigencia', 'A vigência máxima é de '.self::VIGENCIA_MAXIMA_DIAS.' dias.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'seguradoNome.min' => 'Informe o nome completo (3 a 120 caracteres).',
            'seguradoNome.max' => 'Informe o nome completo (3 a 120 caracteres).',
            'email' => 'E-mail inválido.',
            'seguradoNascimento.date_format' => 'Data de nascimento inválida.',
            'seguradoNascimento.before_or_equal' => 'A data de nascimento não pode ser futura.',
            'inicioVigencia.date_format' => 'Data de início inválida.',
            'fimVigencia.date_format' => 'Data de fim inválida.',
            'fimVigencia.after_or_equal' => 'O fim da vigência deve ser igual ou posterior ao início.',
            'destino.in' => 'Destino inválido.',
            'plano.in' => 'Plano inválido.',
            'status.in' => 'Status inválido.',
        ];
    }
}
