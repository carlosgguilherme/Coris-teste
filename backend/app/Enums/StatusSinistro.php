<?php

namespace App\Enums;

enum StatusSinistro: string
{
    case Aberto = 'aberto';
    case EmAnalise = 'em_analise';
    case Aprovado = 'aprovado';
    case Pago = 'pago';
    case Negado = 'negado';

    public function label(): string
    {
        return match ($this) {
            self::Aberto => 'Aberto',
            self::EmAnalise => 'Em análise',
            self::Aprovado => 'Aprovado',
            self::Pago => 'Pago',
            self::Negado => 'Negado',
        };
    }
}
