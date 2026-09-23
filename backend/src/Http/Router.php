<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\HttpException;

final class Router
{
    /** @var array<int, array{method: string, regex: string, handler: callable, publica: bool}> */
    private array $rotas = [];

    public function get(string $caminho, callable $handler, bool $publica = false): self
    {
        return $this->adicionar('GET', $caminho, $handler, $publica);
    }

    public function post(string $caminho, callable $handler, bool $publica = false): self
    {
        return $this->adicionar('POST', $caminho, $handler, $publica);
    }

    public function put(string $caminho, callable $handler, bool $publica = false): self
    {
        return $this->adicionar('PUT', $caminho, $handler, $publica);
    }

    public function delete(string $caminho, callable $handler, bool $publica = false): self
    {
        return $this->adicionar('DELETE', $caminho, $handler, $publica);
    }

    public function encontrar(Request $request): RotaEncontrada
    {
        $metodoNaoPermitido = false;

        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $request->path, $matches)) {
                continue;
            }

            if ($rota['method'] !== $request->method) {
                $metodoNaoPermitido = true;
                continue;
            }

            $parametros = array_map(
                fn (string $valor) => ctype_digit($valor) ? (int) $valor : $valor,
                array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
            );

            return new RotaEncontrada($rota['handler'], $parametros, $rota['publica']);
        }

        throw $metodoNaoPermitido
            ? new HttpException(405, 'Método não permitido.')
            : new HttpException(404, 'Rota não encontrada.');
    }

    private function adicionar(string $method, string $caminho, callable $handler, bool $publica): self
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $caminho);

        $this->rotas[] = [
            'method' => $method,
            'regex' => "#^{$regex}$#",
            'handler' => $handler,
            'publica' => $publica,
        ];

        return $this;
    }
}
