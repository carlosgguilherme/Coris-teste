<?php

namespace App\Models;

use App\Enums\CanalAtendimento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Atendimento extends Model
{
    protected $fillable = ['apolice_id', 'canal', 'tipo', 'inicio', 'tempo_espera_seg', 'dentro_sla', 'nps'];

    protected function casts(): array
    {
        return [
            'canal' => CanalAtendimento::class,
            'inicio' => 'datetime',
            'dentro_sla' => 'boolean',
        ];
    }

    public function apolice(): BelongsTo
    {
        return $this->belongsTo(Apolice::class);
    }
}
