<?php

namespace App\Enums;

enum Cobertura: string
{
    case DespesasMedicas = 'despesas_medicas';
    case Odontologica = 'odontologica';
    case Bagagem = 'bagagem';
    case Cancelamento = 'cancelamento';
    case AtrasoVoo = 'atraso_voo';

    public function label(): string
    {
        return match ($this) {
            self::DespesasMedicas => 'Despesas médicas',
            self::Odontologica => 'Odontológica',
            self::Bagagem => 'Extravio de bagagem',
            self::Cancelamento => 'Cancelamento de viagem',
            self::AtrasoVoo => 'Atraso de voo',
        };
    }
}
