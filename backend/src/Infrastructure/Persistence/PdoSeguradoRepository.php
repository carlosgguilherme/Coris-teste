<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Segurado\Segurado;
use App\Domain\Segurado\SeguradoRepository;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;
use PDO;

final class PdoSeguradoRepository implements SeguradoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorCpf(Cpf $cpf): ?Segurado
    {
        $stmt = $this->pdo->prepare('SELECT * FROM segurados WHERE cpf = :cpf');
        $stmt->execute(['cpf' => $cpf->numero]);
        $linha = $stmt->fetch();

        return $linha ? self::hidratar($linha) : null;
    }

    public function salvar(Segurado $segurado): void
    {
        $dados = [
            'nome' => $segurado->nome(),
            'email' => $segurado->email(),
            'data_nascimento' => $segurado->dataNascimento()->format('Y-m-d'),
            'agora' => date('Y-m-d H:i:s'),
        ];

        if ($segurado->id() === null) {
            $this->pdo->prepare(
                'INSERT INTO segurados (nome, cpf, email, data_nascimento, criado_em)
                 VALUES (:nome, :cpf, :email, :data_nascimento, :agora)'
            )->execute([...$dados, 'cpf' => $segurado->cpf()->numero]);

            $segurado->definirId((int) $this->pdo->lastInsertId());

            return;
        }

        $this->pdo->prepare(
            'UPDATE segurados SET nome = :nome, email = :email, data_nascimento = :data_nascimento, atualizado_em = :agora
             WHERE id = :id'
        )->execute([...$dados, 'id' => $segurado->id()]);
    }

    public static function hidratar(array $linha, string $prefixo = ''): Segurado
    {
        return Segurado::restaurar(
            id: (int) $linha[$prefixo . 'id'],
            nome: $linha[$prefixo . 'nome'],
            cpf: Cpf::from($linha[$prefixo . 'cpf']),
            email: $linha[$prefixo . 'email'],
            dataNascimento: new DateTimeImmutable($linha[$prefixo . 'data_nascimento']),
        );
    }
}
