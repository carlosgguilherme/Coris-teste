<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Auth\AuthService;
use App\Http\Request;
use App\Http\Response;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(Request $request): Response
    {
        $dados = $request->json();

        return Response::json($this->auth->login((string) ($dados['email'] ?? ''), (string) ($dados['senha'] ?? '')));
    }

    public function eu(Request $request): Response
    {
        return Response::json($request->usuario);
    }
}
