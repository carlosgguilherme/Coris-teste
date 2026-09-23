<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Shared\Transacao;
use PDO;
use Throwable;

final class PdoTransacao implements Transacao
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function executar(callable $operacao): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $resultado = $operacao();
            $this->pdo->commit();

            return $resultado;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
