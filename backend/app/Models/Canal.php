<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canal extends Model
{
    protected $table = 'canais';

    protected $fillable = ['codigo', 'nome'];

    public function apolices(): HasMany
    {
        return $this->hasMany(Apolice::class);
    }
}
