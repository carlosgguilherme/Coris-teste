<?php

namespace App\Http\Requests;

use App\Enums\StatusApolice;
use App\Models\Apolice;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SalvarApoliceRequest extends CotacaoRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['sometimes', Rule::in(array_column(StatusApolice::cases(), 'value'))],
        ];
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator) {
                if ($validator->errors()->has('inicioVigencia') || ! $this->inicioFoiInformadoOuAlterado()) {
                    return;
                }

                if (Carbon::parse($this->input('inicioVigencia'))->lt(today())) {
                    $validator->errors()->add('inicioVigencia', 'O início da vigência não pode ser anterior a hoje.');
                }
            },
        ];
    }

    private function inicioFoiInformadoOuAlterado(): bool
    {
        $apolice = $this->route('apolice');

        return ! $apolice instanceof Apolice
            || $apolice->inicio_vigencia->toDateString() !== $this->input('inicioVigencia');
    }
}
