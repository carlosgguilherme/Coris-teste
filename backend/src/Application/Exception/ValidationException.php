<?php

declare(strict_types=1);

namespace App\Application\Exception;

class ValidationException extends \InvalidArgumentException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Os dados informados são inválidos.');
    }
}
