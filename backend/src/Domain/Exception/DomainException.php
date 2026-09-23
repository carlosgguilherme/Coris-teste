<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class DomainException extends \DomainException
{
    public function __construct(string $message, public readonly ?string $campo = null)
    {
        parent::__construct($message);
    }
}
