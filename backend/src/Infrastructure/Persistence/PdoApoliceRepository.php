<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\ApoliceRepository;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\Segurado;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Cpf;
use DateTimeImmutable;
use PDO;

final class PdoApoliceRepository implements ApoliceRepository
{
    private const FORMATO_DATA = 'Y-m-d';
    private const FORMATO_DATA_HORA = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(?string $busca = null, ?StatusApolice $status = null): array
    {
        $sql = 'SELECT * FROM apolices WHERE 1 = 1';
        $parametros = [];

        if ($busca !== null) {
            $sql .= ' AND (segurado_nome LIKE :nome OR numero LIKE :numero OR segurado_email LIKE :email';
            $termo = "%{$busca}%";
            $parametros += ['nome' => $termo, 'numero' => $termo, 'email' => $termo];

            $digitos = preg_replace('/\D/', '', $busca);
            if ($digitos !== '') {
                $sql .= ' OR segurado_cpf LIKE :cpf';
                $parametros['cpf'] = "%{$digitos}%";
            }

            $sql .= ')';
        }

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $parametros['status'] = $status->value;
        }

        $sql .= ' ORDER BY criado_em DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return array_map($this->hidratar(...), $stmt->fetchAll());
    }

    public function buscarPorId(int $id): ?Apolice
    {
        $stmt = $this->pdo->prepare('SELECT * FROM apolices WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();

        return $linha ? $this->hidratar($linha) : null;
    }

    public function salvar(Apolice $apolice): void
    {
        $apolice->id() === null ? $this->inserir($apolice) : $this->atualizar($apolice);
    }

    public function excluir(int $id): void
    {
        $this->pdo->prepare('DELETE FROM apolices WHERE id = :id')->execute(['id' => $id]);
    }

    private function inserir(Apolice $apolice): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO apolices (numero, segurado_nome, segurado_cpf, segurado_email, segurado_nascimento,
                destino, plano, inicio_vigencia, fim_vigencia, valor_premio, status, criado_em, atualizado_em)
             VALUES (:numero, :segurado_nome, :segurado_cpf, :segurado_email, :segurado_nascimento,
                :destino, :plano, :inicio_vigencia, :fim_vigencia, :valor_premio, :status, :criado_em, :atualizado_em)'
        );

        $stmt->execute($this->extrair($apolice));
        $apolice->definirId((int) $this->pdo->lastInsertId());
    }

    private function atualizar(Apolice $apolice): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE apolices SET
                numero = :numero,
                segurado_nome = :segurado_nome,
                segurado_cpf = :segurado_cpf,
                segurado_email = :segurado_email,
                segurado_nascimento = :segurado_nascimento,
                destino = :destino,
                plano = :plano,
                inicio_vigencia = :inicio_vigencia,
                fim_vigencia = :fim_vigencia,
                valor_premio = :valor_premio,
                status = :status,
                criado_em = :criado_em,
                atualizado_em = :atualizado_em
             WHERE id = :id'
        );

        $stmt->execute([...$this->extrair($apolice), 'id' => $apolice->id()]);
    }

    private function extrair(Apolice $apolice): array
    {
        $segurado = $apolice->segurado();

        return [
            'numero' => $apolice->numero(),
            'segurado_nome' => $segurado->nome,
            'segurado_cpf' => $segurado->cpf->numero,
            'segurado_email' => $segurado->email,
            'segurado_nascimento' => $segurado->dataNascimento->format(self::FORMATO_DATA),
            'destino' => $apolice->destino()->value,
            'plano' => $apolice->plano()->value,
            'inicio_vigencia' => $apolice->vigencia()->inicio->format(self::FORMATO_DATA),
            'fim_vigencia' => $apolice->vigencia()->fim->format(self::FORMATO_DATA),
            'valor_premio' => number_format($apolice->valorPremio(), 2, '.', ''),
            'status' => $apolice->status()->value,
            'criado_em' => $apolice->criadoEm()->format(self::FORMATO_DATA_HORA),
            'atualizado_em' => $apolice->atualizadoEm()?->format(self::FORMATO_DATA_HORA),
        ];
    }

    private function hidratar(array $linha): Apolice
    {
        return Apolice::restaurar(
            id: (int) $linha['id'],
            numero: $linha['numero'],
            segurado: new Segurado(
                nome: $linha['segurado_nome'],
                cpf: Cpf::from($linha['segurado_cpf']),
                email: $linha['segurado_email'],
                dataNascimento: new DateTimeImmutable($linha['segurado_nascimento']),
            ),
            destino: Destino::from($linha['destino']),
            plano: Plano::from($linha['plano']),
            vigencia: new Vigencia(
                new DateTimeImmutable($linha['inicio_vigencia']),
                new DateTimeImmutable($linha['fim_vigencia']),
            ),
            valorPremio: (float) $linha['valor_premio'],
            status: StatusApolice::from($linha['status']),
            criadoEm: new DateTimeImmutable($linha['criado_em']),
            atualizadoEm: $linha['atualizado_em'] ? new DateTimeImmutable($linha['atualizado_em']) : null,
        );
    }
}
