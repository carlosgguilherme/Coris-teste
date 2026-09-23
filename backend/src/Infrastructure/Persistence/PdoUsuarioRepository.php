<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Usuario\Usuario;
use App\Domain\Usuario\UsuarioRepository;
use PDO;

final class PdoUsuarioRepository implements UsuarioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $linha = $stmt->fetch();

        return $linha
            ? Usuario::restaurar((int) $linha['id'], $linha['nome'], $linha['email'], $linha['senha_hash'])
            : null;
    }

    public function existeAlgum(): bool
    {
        return (bool) $this->pdo->query('SELECT 1 FROM usuarios LIMIT 1')->fetchColumn();
    }

    public function salvar(Usuario $usuario): void
    {
        $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, criado_em) VALUES (:nome, :email, :senha_hash, :criado_em)'
        )->execute([
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'senha_hash' => $usuario->senhaHash(),
            'criado_em' => date('Y-m-d H:i:s'),
        ]);

        $usuario->definirId((int) $this->pdo->lastInsertId());
    }
}
