<?php

namespace App\Models;

use App\Enums\Cobertura;
use App\Enums\StatusSinistro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sinistro extends Model
{
    protected $fillable = [
        'numero', 'apolice_id', 'cobertura', 'data_ocorrencia', 'data_aviso',
        'valor_reclamado_centavos', 'valor_pago_centavos', 'status', 'motivo_negativa',
    ];

    protected function casts(): array
    {
        return [
            'cobertura' => Cobertura::class,
            'status' => StatusSinistro::class,
            'data_ocorrencia' => 'date',
            'data_aviso' => 'date',
        ];
    }

    public function apolice(): BelongsTo
    {
        return $this->belongsTo(Apolice::class);
    }
}
