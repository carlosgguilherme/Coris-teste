<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\HttpException;

final class Router
{
    /** @var array<int, array{method: string, regex: string, handler: callable}> */
    private array $rotas = [];

    public function get(string $caminho, callable $handler): self
    {
        return $this->adicionar('GET', $caminho, $handler);
    }

    public function post(string $caminho, callable $handler): self
    {
        return $this->adicionar('POST', $caminho, $handler);
    }

    public function put(string $caminho, callable $handler): self
    {
        return $this->adicionar('PUT', $caminho, $handler);
    }

    public function delete(string $caminho, callable $handler): self
    {
        return $this->adicionar('DELETE', $caminho, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $metodosPermitidos = [];

        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $request->path, $matches)) {
                continue;
            }

            if ($rota['method'] !== $request->method) {
                $metodosPermitidos[] = $rota['method'];
                continue;
            }

            $parametros = array_map(
                fn (string $valor) => ctype_digit($valor) ? (int) $valor : $valor,
                array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
            );

            return ($rota['handler'])($request, ...$parametros);
        }

        if ($metodosPermitidos !== []) {
            throw new HttpException(405, 'Método não permitido.');
        }

        throw new HttpException(404, 'Rota não encontrada.');
    }

    private function adicionar(string $method, string $caminho, callable $handler): self
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $caminho);

        $this->rotas[] = [
            'method' => $method,
            'regex' => "#^{$regex}$#",
            'handler' => $handler,
        ];

        return $this;
    }
}
