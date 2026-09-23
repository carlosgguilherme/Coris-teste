<?php

declare(strict_types=1);

namespace App;

use App\Application\Apolice\ApoliceService;
use App\Application\Apolice\ApoliceValidator;
use App\Application\Apolice\GeradorNumeroAleatorio;
use App\Application\Auth\AuthService;
use App\Application\Premio\CalculadoraPremioViagem;
use App\Domain\Shared\Relogio;
use App\Domain\Usuario\UsuarioRepository;
use App\Http\Controller\ApoliceController;
use App\Http\Controller\AuthController;
use App\Http\Controller\OpcoesController;
use App\Http\Kernel;
use App\Http\Response;
use App\Http\Router;
use App\Infrastructure\Auth\JwtEmissorToken;
use App\Infrastructure\Config\Env;
use App\Infrastructure\Database\PdoTransacao;
use App\Infrastructure\Persistence\PdoApoliceRepository;
use App\Infrastructure\Persistence\PdoEndossoRepository;
use App\Infrastructure\Persistence\PdoSeguradoRepository;
use App\Infrastructure\Persistence\PdoUsuarioRepository;
use App\Infrastructure\Tempo\RelogioDoSistema;
use PDO;

final class Container
{
    private ?ApoliceService $apoliceService = null;
    private ?AuthService $authService = null;
    private readonly Relogio $relogio;

    public function __construct(private readonly PDO $pdo, ?Relogio $relogio = null)
    {
        $this->relogio = $relogio ?? new RelogioDoSistema();
    }

    public function apoliceService(): ApoliceService
    {
        return $this->apoliceService ??= new ApoliceService(
            new PdoApoliceRepository($this->pdo),
            new PdoSeguradoRepository($this->pdo),
            new PdoEndossoRepository($this->pdo),
            new PdoTransacao($this->pdo),
            new ApoliceValidator(),
            new CalculadoraPremioViagem(),
            new GeradorNumeroAleatorio(),
            $this->relogio,
        );
    }

    public function authService(): AuthService
    {
        return $this->authService ??= new AuthService(
            $this->usuarios(),
            new JwtEmissorToken((string) Env::get('JWT_SECRET'), $this->relogio),
        );
    }

    public function usuarios(): UsuarioRepository
    {
        return new PdoUsuarioRepository($this->pdo);
    }

    public function kernel(): Kernel
    {
        return new Kernel(
            $this->rotas(),
            $this->authService(),
            Env::get('CORS_ALLOWED_ORIGIN', '*'),
            Env::bool('APP_DEBUG'),
        );
    }

    private function rotas(): Router
    {
        $apolices = new ApoliceController($this->apoliceService());
        $auth = new AuthController($this->authService());

        return (new Router())
            ->get('/api/health', fn () => Response::json(['status' => 'ok']), publica: true)
            ->post('/api/auth/login', $auth->login(...), publica: true)
            ->get('/api/auth/eu', $auth->eu(...))
            ->get('/api/opcoes', new OpcoesController())
            ->get('/api/apolices', $apolices->index(...))
            ->get('/api/apolices/resumo', $apolices->resumo(...))
            ->post('/api/apolices', $apolices->store(...))
            ->post('/api/apolices/cotacao', $apolices->cotacao(...))
            ->get('/api/apolices/{id}', $apolices->show(...))
            ->get('/api/apolices/{id}/endossos', $apolices->endossos(...))
            ->put('/api/apolices/{id}', $apolices->update(...))
            ->delete('/api/apolices/{id}', $apolices->destroy(...));
    }
}
