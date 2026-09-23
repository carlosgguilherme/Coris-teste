<?php

namespace App\Models;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Enums\StatusApolice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apolice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'apolices';

    protected $fillable = [
        'numero',
        'segurado_id',
        'destino',
        'plano',
        'inicio_vigencia',
        'fim_vigencia',
        'valor_premio_centavos',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'destino' => Destino::class,
            'plano' => Plano::class,
            'status' => StatusApolice::class,
            'inicio_vigencia' => 'date',
            'fim_vigencia' => 'date',
            'valor_premio_centavos' => 'integer',
        ];
    }

    public function segurado(): BelongsTo
    {
        return $this->belongsTo(Segurado::class);
    }

    public function dias(): int
    {
        return (int) $this->inicio_vigencia->diffInDays($this->fim_vigencia) + 1;
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (blank($termo)) {
            return $query;
        }

        $digitos = preg_replace('/\D/', '', $termo);

        return $query->where(function (Builder $query) use ($termo, $digitos) {
            $query->where('numero', 'like', "%{$termo}%")
                ->orWhereHas('segurado', function (Builder $segurado) use ($termo, $digitos) {
                    $segurado->where('nome', 'like', "%{$termo}%")
                        ->orWhere('email', 'like', "%{$termo}%");

                    if ($digitos !== '') {
                        $segurado->orWhere('cpf', 'like', "%{$digitos}%");
                    }
                });
        });
    }
}
