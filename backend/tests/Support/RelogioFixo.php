<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Relogio;
use DateTimeImmutable;

final class RelogioFixo implements Relogio
{
    private DateTimeImmutable $agora;

    public function __construct(string $agora = '2026-09-20 10:00:00')
    {
        $this->agora = new DateTimeImmutable($agora);
    }

    public function agora(): DateTimeImmutable
    {
        return $this->agora;
    }

    public function hoje(): DateTimeImmutable
    {
        return $this->agora->setTime(0, 0);
    }
}
