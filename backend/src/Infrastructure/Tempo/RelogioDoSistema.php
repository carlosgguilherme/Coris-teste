<?php

declare(strict_types=1);

namespace App\Infrastructure\Tempo;

use App\Domain\Shared\Relogio;
use DateTimeImmutable;

final class RelogioDoSistema implements Relogio
{
    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public function hoje(): DateTimeImmutable
    {
        return new DateTimeImmutable('today');
    }
}
