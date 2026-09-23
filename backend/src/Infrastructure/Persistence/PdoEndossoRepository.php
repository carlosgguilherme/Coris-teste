<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Apolice\Endosso;
use App\Domain\Apolice\EndossoRepository;
use App\Domain\Shared\Dinheiro;
use DateTimeImmutable;
use PDO;

final class PdoEndossoRepository implements EndossoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listarPorApolice(int $apoliceId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM endossos WHERE apolice_id = :apolice_id ORDER BY numero DESC');
        $stmt->execute(['apolice_id' => $apoliceId]);

        return array_map(fn (array $linha) => Endosso::restaurar(
            id: (int) $linha['id'],
            apoliceId: (int) $linha['apolice_id'],
            numero: (int) $linha['numero'],
            alteracoes: json_decode($linha['alteracoes'], true, flags: JSON_THROW_ON_ERROR),
            premioAnterior: Dinheiro::centavos((int) $linha['premio_anterior_centavos']),
            premioNovo: Dinheiro::centavos((int) $linha['premio_novo_centavos']),
            usuario: $linha['usuario'],
            criadoEm: new DateTimeImmutable($linha['criado_em']),
        ), $stmt->fetchAll());
    }

    public function salvar(Endosso $endosso): void
    {
        $proximo = $this->pdo->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM endossos WHERE apolice_id = :apolice_id');
        $proximo->execute(['apolice_id' => $endosso->apoliceId()]);
        $numero = (int) $proximo->fetchColumn();

        $this->pdo->prepare(
            'INSERT INTO endossos (apolice_id, numero, alteracoes, premio_anterior_centavos, premio_novo_centavos, usuario, criado_em)
             VALUES (:apolice_id, :numero, :alteracoes, :premio_anterior, :premio_novo, :usuario, :criado_em)'
        )->execute([
            'apolice_id' => $endosso->apoliceId(),
            'numero' => $numero,
            'alteracoes' => json_encode($endosso->alteracoes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'premio_anterior' => $endosso->premioAnterior->centavos,
            'premio_novo' => $endosso->premioNovo->centavos,
            'usuario' => $endosso->usuario,
            'criado_em' => $endosso->criadoEm->format('Y-m-d H:i:s'),
        ]);

        $endosso->definirIdentificacao((int) $this->pdo->lastInsertId(), $numero);
    }
}
