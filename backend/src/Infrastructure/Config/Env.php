<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

final class Env
{
    public static function load(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }

        $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
            $valor = trim($valor, "\"'");

            if (getenv($chave) === false) {
                putenv("{$chave}={$valor}");
                $_ENV[$chave] = $valor;
            }
        }
    }

    public static function get(string $chave, ?string $padrao = null): ?string
    {
        $valor = $_ENV[$chave] ?? getenv($chave);

        return $valor === false || $valor === '' ? $padrao : (string) $valor;
    }

    public static function bool(string $chave, bool $padrao = false): bool
    {
        $valor = self::get($chave);

        return $valor === null ? $padrao : filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
