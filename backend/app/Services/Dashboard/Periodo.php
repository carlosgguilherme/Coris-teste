<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/** Intervalo de datas do filtro da dashboard e o período anterior, de mesmo tamanho, para comparação. */
class Periodo
{
    public const OPCOES = ['30d' => 30, '90d' => 90, '12m' => 365, '24m' => 730];

    public const PADRAO = '12m';

    private function __construct(public readonly Carbon $inicio, public readonly Carbon $fim) {}

    public static function de(?string $codigo): self
    {
        $dias = self::OPCOES[$codigo ?? self::PADRAO] ?? throw new InvalidArgumentException('Período inválido.');
        $fim = now();

        return new self($fim->copy()->subDays($dias), $fim);
    }

    public function anterior(): self
    {
        $dias = $this->inicio->diffInDays($this->fim);

        return new self($this->inicio->copy()->subDays($dias), $this->inicio->copy());
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function intervalo(): array
    {
        return [$this->inicio, $this->fim];
    }
}
