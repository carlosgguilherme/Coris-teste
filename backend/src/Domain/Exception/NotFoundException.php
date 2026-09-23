<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class NotFoundException extends \RuntimeException
{
    public static function apolice(int $id): self
    {
        return new self("Apólice {$id} não encontrada.");
    }
}
