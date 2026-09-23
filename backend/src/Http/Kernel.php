<?php

declare(strict_types=1);

namespace App\Http;

use App\Application\Auth\AuthService;
use App\Application\Auth\NaoAutenticadoException;
use App\Application\Exception\ValidationException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\NotFoundException;
use App\Http\Exception\HttpException;
use Throwable;

final class Kernel
{
    public function __construct(
        private readonly Router $router,
        private readonly AuthService $auth,
        private readonly string $origemPermitida = '*',
        private readonly bool $debug = false,
    ) {
    }

    public function handle(Request $request): Response
    {
        $response = $request->method === 'OPTIONS'
            ? Response::noContent()
            : $this->executar($request);

        return $response->withHeaders($this->cabecalhosCors());
    }

    private function executar(Request $request): Response
    {
        try {
            $rota = $this->router->encontrar($request);

            if (!$rota->publica) {
                $request = $request->comUsuario($this->auth->autenticar($request->header('Authorization')));
            }

            return $rota->executar($request);
        } catch (ValidationException $e) {
            return Response::error($e->getMessage(), 422, $e->errors);
        } catch (DomainException $e) {
            return Response::error($e->getMessage(), 422, $e->campo ? [$e->campo => $e->getMessage()] : []);
        } catch (NaoAutenticadoException $e) {
            return Response::error($e->getMessage(), 401);
        } catch (NotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (HttpException $e) {
            return Response::error($e->getMessage(), $e->status);
        } catch (\JsonException) {
            return Response::error('JSON inválido no corpo da requisição.', 400);
        } catch (Throwable $e) {
            error_log((string) $e);

            return Response::error($this->debug ? $e->getMessage() : 'Erro interno no servidor.', 500);
        }
    }

    private function cabecalhosCors(): array
    {
        return [
            'Access-Control-Allow-Origin' => $this->origemPermitida,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Max-Age' => '86400',
        ];
    }
}
