<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Auth\AuthService;
use App\Application\Auth\NaoAutenticadoException;
use App\Domain\Usuario\Usuario;
use App\Domain\Usuario\UsuarioRepository;
use App\Infrastructure\Auth\JwtEmissorToken;
use PHPUnit\Framework\TestCase;
use Tests\Support\RelogioFixo;

final class AuthServiceTest extends TestCase
{
    private const SEGREDO = 'segredo-de-teste-com-pelo-menos-32-caracteres';

    private UsuarioRepository $usuarios;

    protected function setUp(): void
    {
        $usuario = Usuario::cadastrar('Operador', 'operador@email.com', 'senha-forte-123');
        $usuario->definirId(1);

        $this->usuarios = new class ($usuario) implements UsuarioRepository {
            public function __construct(private readonly Usuario $usuario)
            {
            }

            public function buscarPorEmail(string $email): ?Usuario
            {
                return $email === $this->usuario->email ? $this->usuario : null;
            }

            public function existeAlgum(): bool
            {
                return true;
            }

            public function salvar(Usuario $usuario): void
            {
            }
        };
    }

    public function testLoginDevolveTokenValido(): void
    {
        $auth = $this->auth(new RelogioFixo());

        $sessao = $auth->login(' OPERADOR@email.com ', 'senha-forte-123');

        $this->assertSame(['nome' => 'Operador', 'email' => 'operador@email.com'], $sessao['usuario']);
        $this->assertSame($sessao['usuario'], $auth->autenticar("Bearer {$sessao['token']}"));
    }

    public function testSenhaErradaNaoAutentica(): void
    {
        $this->expectException(NaoAutenticadoException::class);

        $this->auth(new RelogioFixo())->login('operador@email.com', 'senha-errada');
    }

    public function testTokenExpiradoNaoAutentica(): void
    {
        $token = $this->auth(new RelogioFixo('2026-09-20 08:00:00'))->login('operador@email.com', 'senha-forte-123')['token'];

        $this->expectException(NaoAutenticadoException::class);

        $this->auth(new RelogioFixo('2026-09-20 17:00:00'))->autenticar("Bearer {$token}");
    }

    public function testCabecalhoAusenteOuMalFormadoNaoAutentica(): void
    {
        $auth = $this->auth(new RelogioFixo());

        foreach ([null, 'token-sem-bearer', 'Bearer token-invalido'] as $cabecalho) {
            try {
                $auth->autenticar($cabecalho);
                $this->fail("Cabeçalho '{$cabecalho}' não deveria autenticar.");
            } catch (NaoAutenticadoException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testSenhaCurtaNaoPodeSerCadastrada(): void
    {
        $this->expectExceptionMessage('A senha deve ter pelo menos 8 caracteres.');

        Usuario::cadastrar('Teste', 'teste@email.com', '1234567');
    }

    private function auth(RelogioFixo $relogio): AuthService
    {
        return new AuthService($this->usuarios, new JwtEmissorToken(self::SEGREDO, $relogio));
    }
}
