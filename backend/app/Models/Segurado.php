<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segurado extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'cpf', 'email', 'data_nascimento'];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
        ];
    }

    public function apolices(): HasMany
    {
        return $this->hasMany(Apolice::class);
    }

    public function cpfFormatado(): string
    {
        return vsprintf('%s.%s.%s-%s', str_split($this->cpf, 3));
    }
}
