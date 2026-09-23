<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Config\Env;
use PDO;

final class ConnectionFactory
{
    public static function fromEnv(string $raizProjeto): PDO
    {
        $driver = Env::get('DB_CONNECTION', 'sqlite');

        return match ($driver) {
            'mysql' => self::mysql(),
            'sqlite' => self::sqlite(self::caminhoSqlite($raizProjeto)),
            default => throw new \RuntimeException("Driver de banco não suportado: {$driver}"),
        };
    }

    public static function sqlite(string $caminho): PDO
    {
        $pdo = new PDO("sqlite:{$caminho}", options: self::opcoesPadrao());
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    private static function mysql(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '3306'),
            Env::get('DB_DATABASE', 'coris_seguros'),
        );

        $opcoes = self::opcoesPadrao();

        if ($certificado = Env::get('MYSQL_ATTR_SSL_CA')) {
            $opcoes[PDO::MYSQL_ATTR_SSL_CA] = $certificado;
        }

        return new PDO($dsn, Env::get('DB_USERNAME', 'root'), Env::get('DB_PASSWORD', ''), $opcoes);
    }

    private static function caminhoSqlite(string $raizProjeto): string
    {
        $caminho = Env::get('DB_DATABASE', 'database/coris_seguros.sqlite');

        if ($caminho === ':memory:' || str_starts_with($caminho, '/')) {
            return $caminho;
        }

        return $raizProjeto . '/' . $caminho;
    }

    private static function opcoesPadrao(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
