<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        private readonly string $rawBody = '',
        public readonly array $headers = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            path: rtrim($path, '/') ?: '/',
            query: $_GET,
            rawBody: (string) file_get_contents('php://input'),
            headers: array_change_key_case(getallheaders() ?: [], CASE_LOWER),
        );
    }

    /** @throws \JsonException */
    public function json(): array
    {
        if (trim($this->rawBody) === '') {
            return [];
        }

        $dados = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);

        return is_array($dados) ? $dados : [];
    }

    public function query(string $chave): ?string
    {
        $valor = $this->query[$chave] ?? null;

        return is_string($valor) ? $valor : null;
    }

    public function header(string $nome): ?string
    {
        return $this->headers[strtolower($nome)] ?? null;
    }
}
