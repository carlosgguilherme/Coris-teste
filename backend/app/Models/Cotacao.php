<?php

namespace App\Models;

use App\Enums\StatusCotacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotacao extends Model
{
    protected $table = 'cotacoes';

    protected $fillable = [
        'codigo', 'canal_id', 'campanha_id', 'apolice_id', 'destino', 'plano', 'dias',
        'valor_calculado_centavos', 'device', 'status', 'etapa_abandono',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusCotacao::class,
        ];
    }

    public function canal(): BelongsTo
    {
        return $this->belongsTo(Canal::class);
    }

    public function campanha(): BelongsTo
    {
        return $this->belongsTo(Campanha::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(FunilEvento::class);
    }
}
