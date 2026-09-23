<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;

interface Relogio
{
    public function agora(): DateTimeImmutable;

    public function hoje(): DateTimeImmutable;
}
