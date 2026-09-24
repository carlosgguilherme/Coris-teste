<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunilEvento extends Model
{
    public $timestamps = false;

    protected $table = 'funil_eventos';

    protected $fillable = ['cotacao_id', 'etapa', 'utm_source', 'device', 'ocorrido_em'];

    protected function casts(): array
    {
        return [
            'ocorrido_em' => 'datetime',
        ];
    }

    public function cotacao(): BelongsTo
    {
        return $this->belongsTo(Cotacao::class);
    }
}
