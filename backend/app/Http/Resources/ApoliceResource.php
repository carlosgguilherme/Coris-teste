<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Apolice */
class ApoliceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'seguradoId' => $this->segurado->id,
            'seguradoNome' => $this->segurado->nome,
            'seguradoCpf' => $this->segurado->cpfFormatado(),
            'seguradoEmail' => $this->segurado->email,
            'seguradoNascimento' => $this->segurado->data_nascimento->toDateString(),
            'destino' => $this->destino->value,
            'destinoLabel' => $this->destino->label(),
            'plano' => $this->plano->value,
            'planoLabel' => $this->plano->label(),
            'inicioVigencia' => $this->inicio_vigencia->toDateString(),
            'fimVigencia' => $this->fim_vigencia->toDateString(),
            'dias' => $this->dias(),
            'valorPremioCentavos' => $this->valor_premio_centavos,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'criadoEm' => $this->created_at?->toIso8601String(),
            'atualizadoEm' => $this->updated_at?->eq($this->created_at) ? null : $this->updated_at?->toIso8601String(),
        ];
    }
}
