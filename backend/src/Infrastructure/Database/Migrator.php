<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $diretorioSchemas,
    ) {
    }

    public function migrar(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $arquivo = "{$this->diretorioSchemas}/{$driver}.sql";

        if (!is_file($arquivo)) {
            throw new \RuntimeException("Schema não encontrado para o driver {$driver}.");
        }

        $this->pdo->exec((string) file_get_contents($arquivo));
    }
}
