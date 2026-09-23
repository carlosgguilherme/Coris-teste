<?php

declare(strict_types=1);

namespace App\Http\Resource;

use App\Domain\Apolice\Apolice;

final class ApoliceResource
{
    public static function toArray(Apolice $apolice): array
    {
        $segurado = $apolice->segurado();
        $vigencia = $apolice->vigencia();

        return [
            'id' => $apolice->id(),
            'numero' => $apolice->numero(),
            'seguradoId' => $segurado->id(),
            'seguradoNome' => $segurado->nome(),
            'seguradoCpf' => $segurado->cpf()->formatado(),
            'seguradoEmail' => $segurado->email(),
            'seguradoNascimento' => $segurado->dataNascimento()->format('Y-m-d'),
            'destino' => $apolice->destino()->value,
            'destinoLabel' => $apolice->destino()->label(),
            'plano' => $apolice->plano()->value,
            'planoLabel' => $apolice->plano()->label(),
            'inicioVigencia' => $vigencia->inicio->format('Y-m-d'),
            'fimVigencia' => $vigencia->fim->format('Y-m-d'),
            'dias' => $vigencia->dias(),
            'valorPremioCentavos' => $apolice->valorPremio()->centavos,
            'status' => $apolice->status()->value,
            'statusLabel' => $apolice->status()->label(),
            'criadoEm' => $apolice->criadoEm()->format(DATE_ATOM),
            'atualizadoEm' => $apolice->atualizadoEm()?->format(DATE_ATOM),
        ];
    }

    /** @param Apolice[] $apolices */
    public static function collection(array $apolices): array
    {
        return array_map(self::toArray(...), $apolices);
    }
}
