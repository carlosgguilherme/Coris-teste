<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(
        public readonly int $status = 200,
        public readonly mixed $body = null,
        private array $headers = [],
    ) {
    }

    public static function json(mixed $body, int $status = 200): self
    {
        return new self($status, $body, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    public static function error(string $mensagem, int $status, array $erros = []): self
    {
        $body = ['message' => $mensagem];

        if ($erros !== []) {
            $body['errors'] = $erros;
        }

        return self::json($body, $status);
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = [...$this->headers, ...$headers];

        return $clone;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $nome => $valor) {
            header("{$nome}: {$valor}");
        }

        if ($this->body !== null && $this->status !== 204) {
            echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        }
    }
}
