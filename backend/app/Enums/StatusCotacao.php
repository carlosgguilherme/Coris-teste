<?php

namespace App\Enums;

/** As quatro primeiras são as etapas do funil, na ordem em que o cliente avança. */
enum StatusCotacao: string
{
    case Iniciada = 'iniciada';
    case Calculada = 'calculada';
    case DadosPreenchidos = 'dados_preenchidos';
    case Pagamento = 'pagamento';
    case Convertida = 'convertida';
    case Abandonada = 'abandonada';

    public function label(): string
    {
        return match ($this) {
            self::Iniciada => 'Cotação iniciada',
            self::Calculada => 'Preço calculado',
            self::DadosPreenchidos => 'Dados preenchidos',
            self::Pagamento => 'Pagamento',
            self::Convertida => 'Apólice emitida',
            self::Abandonada => 'Abandonada',
        };
    }

    /** @return list<self> */
    public static function etapasDoFunil(): array
    {
        return [self::Iniciada, self::Calculada, self::DadosPreenchidos, self::Pagamento, self::Convertida];
    }
}
