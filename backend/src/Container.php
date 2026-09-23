<?php

declare(strict_types=1);

namespace App;

use App\Application\Apolice\ApoliceService;
use App\Application\Apolice\ApoliceValidator;
use App\Application\Apolice\GeradorNumeroAleatorio;
use App\Application\Premio\CalculadoraPremioViagem;
use App\Http\Controller\ApoliceController;
use App\Http\Controller\OpcoesController;
use App\Http\Kernel;
use App\Http\Response;
use App\Http\Router;
use App\Infrastructure\Config\Env;
use App\Infrastructure\Persistence\PdoApoliceRepository;
use PDO;

final class Container
{
    private ?ApoliceService $apoliceService = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function apoliceService(): ApoliceService
    {
        return $this->apoliceService ??= new ApoliceService(
            new PdoApoliceRepository($this->pdo),
            new ApoliceValidator(),
            new CalculadoraPremioViagem(),
            new GeradorNumeroAleatorio(),
        );
    }

    public function kernel(): Kernel
    {
        return new Kernel(
            $this->rotas(),
            Env::get('CORS_ALLOWED_ORIGIN', '*'),
            Env::bool('APP_DEBUG'),
        );
    }

    private function rotas(): Router
    {
        $apolices = new ApoliceController($this->apoliceService());

        return (new Router())
            ->get('/api/health', fn () => Response::json(['status' => 'ok']))
            ->get('/api/opcoes', new OpcoesController())
            ->get('/api/apolices', $apolices->index(...))
            ->post('/api/apolices', $apolices->store(...))
            ->post('/api/apolices/cotacao', $apolices->cotacao(...))
            ->get('/api/apolices/{id}', $apolices->show(...))
            ->put('/api/apolices/{id}', $apolices->update(...))
            ->delete('/api/apolices/{id}', $apolices->destroy(...));
    }
}
