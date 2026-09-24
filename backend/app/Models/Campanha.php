<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campanha extends Model
{
    protected $fillable = ['nome', 'utm_source', 'utm_campaign', 'orcamento_centavos', 'investimento_centavos', 'inicio', 'fim'];

    protected function casts(): array
    {
        return [
            'inicio' => 'date',
            'fim' => 'date',
        ];
    }

    public function apolices(): HasMany
    {
        return $this->hasMany(Apolice::class);
    }
}
