<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Apolice\Apolice;
use App\Domain\Apolice\ApoliceRepository;
use App\Domain\Apolice\Destino;
use App\Domain\Apolice\FiltroApolices;
use App\Domain\Apolice\Plano;
use App\Domain\Apolice\StatusApolice;
use App\Domain\Apolice\Vigencia;
use App\Domain\Shared\Dinheiro;
use App\Domain\Shared\Pagina;
use DateTimeImmutable;
use PDO;

final class PdoApoliceRepository implements ApoliceRepository
{
    private const FORMATO_DATA = 'Y-m-d';
    private const FORMATO_DATA_HORA = 'Y-m-d H:i:s';

    private const SELECT = 'SELECT a.*, s.id AS s_id, s.nome AS s_nome, s.cpf AS s_cpf, s.email AS s_email,
            s.data_nascimento AS s_data_nascimento
        FROM apolices a
        INNER JOIN segurados s ON s.id = a.segurado_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(FiltroApolices $filtro): Pagina
    {
        [$where, $parametros] = $this->condicoes($filtro);

        $total = $this->pdo->prepare("SELECT COUNT(*) FROM apolices a INNER JOIN segurados s ON s.id = a.segurado_id {$where}");
        $total->execute($parametros);

        $stmt = $this->pdo->prepare(self::SELECT . " {$where} ORDER BY a.criado_em DESC, a.id DESC LIMIT :limite OFFSET :deslocamento");
        foreach ($parametros as $nome => $valor) {
            $stmt->bindValue($nome, $valor);
        }
        $stmt->bindValue('limite', $filtro->porPagina, PDO::PARAM_INT);
        $stmt->bindValue('deslocamento', $filtro->deslocamento(), PDO::PARAM_INT);
        $stmt->execute();

        return new Pagina(
            itens: array_map($this->hidratar(...), $stmt->fetchAll()),
            total: (int) $total->fetchColumn(),
            pagina: $filtro->pagina,
            porPagina: $filtro->porPagina,
        );
    }

    public function resumo(): array
    {
        $linha = $this->pdo->query(
            "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = 'ativa' THEN 1 ELSE 0 END) AS ativas,
                SUM(CASE WHEN status = 'ativa' THEN valor_premio_centavos ELSE 0 END) AS premio_ativas
             FROM apolices WHERE excluido_em IS NULL"
        )->fetch();

        return [
            'total' => (int) $linha['total'],
            'ativas' => (int) $linha['ativas'],
            'premioAtivasCentavos' => (int) $linha['premio_ativas'],
        ];
    }

    public function buscarPorId(int $id): ?Apolice
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE a.id = :id AND a.excluido_em IS NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();

        return $linha ? $this->hidratar($linha) : null;
    }

    public function numeroExiste(string $numero): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM apolices WHERE numero = :numero');
        $stmt->execute(['numero' => $numero]);

        return (bool) $stmt->fetchColumn();
    }

    public function salvar(Apolice $apolice): void
    {
        $apolice->id() === null ? $this->inserir($apolice) : $this->atualizar($apolice);
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function condicoes(FiltroApolices $filtro): array
    {
        $condicoes = ['a.excluido_em IS NULL'];
        $parametros = [];

        if ($filtro->busca !== null && trim($filtro->busca) !== '') {
            $termo = '%' . trim($filtro->busca) . '%';
            $busca = ['s.nome LIKE :nome', 'a.numero LIKE :numero', 's.email LIKE :email'];
            $parametros += ['nome' => $termo, 'numero' => $termo, 'email' => $termo];

            $digitos = preg_replace('/\D/', '', $filtro->busca);
            if ($digitos !== '') {
                $busca[] = 's.cpf LIKE :cpf';
                $parametros['cpf'] = "%{$digitos}%";
            }

            $condicoes[] = '(' . implode(' OR ', $busca) . ')';
        }

        if ($filtro->status !== null) {
            $condicoes[] = 'a.status = :status';
            $parametros['status'] = $filtro->status->value;
        }

        return ['WHERE ' . implode(' AND ', $condicoes), $parametros];
    }

    private function inserir(Apolice $apolice): void
    {
        $this->pdo->prepare(
            'INSERT INTO apolices (numero, segurado_id, destino, plano, inicio_vigencia, fim_vigencia,
                valor_premio_centavos, status, criado_em, atualizado_em, excluido_em)
             VALUES (:numero, :segurado_id, :destino, :plano, :inicio_vigencia, :fim_vigencia,
                :valor_premio_centavos, :status, :criado_em, :atualizado_em, :excluido_em)'
        )->execute($this->extrair($apolice));

        $apolice->definirId((int) $this->pdo->lastInsertId());
    }

    private function atualizar(Apolice $apolice): void
    {
        $this->pdo->prepare(
            'UPDATE apolices SET
                numero = :numero,
                segurado_id = :segurado_id,
                destino = :destino,
                plano = :plano,
                inicio_vigencia = :inicio_vigencia,
                fim_vigencia = :fim_vigencia,
                valor_premio_centavos = :valor_premio_centavos,
                status = :status,
                criado_em = :criado_em,
                atualizado_em = :atualizado_em,
                excluido_em = :excluido_em
             WHERE id = :id'
        )->execute([...$this->extrair($apolice), 'id' => $apolice->id()]);
    }

    private function extrair(Apolice $apolice): array
    {
        return [
            'numero' => $apolice->numero(),
            'segurado_id' => $apolice->segurado()->id(),
            'destino' => $apolice->destino()->value,
            'plano' => $apolice->plano()->value,
            'inicio_vigencia' => $apolice->vigencia()->inicio->format(self::FORMATO_DATA),
            'fim_vigencia' => $apolice->vigencia()->fim->format(self::FORMATO_DATA),
            'valor_premio_centavos' => $apolice->valorPremio()->centavos,
            'status' => $apolice->status()->value,
            'criado_em' => $apolice->criadoEm()->format(self::FORMATO_DATA_HORA),
            'atualizado_em' => $apolice->atualizadoEm()?->format(self::FORMATO_DATA_HORA),
            'excluido_em' => $apolice->excluidoEm()?->format(self::FORMATO_DATA_HORA),
        ];
    }

    private function hidratar(array $linha): Apolice
    {
        return Apolice::restaurar(
            id: (int) $linha['id'],
            numero: $linha['numero'],
            segurado: PdoSeguradoRepository::hidratar($linha, 's_'),
            destino: Destino::from($linha['destino']),
            plano: Plano::from($linha['plano']),
            vigencia: new Vigencia(
                new DateTimeImmutable($linha['inicio_vigencia']),
                new DateTimeImmutable($linha['fim_vigencia']),
            ),
            valorPremio: Dinheiro::centavos((int) $linha['valor_premio_centavos']),
            status: StatusApolice::from($linha['status']),
            criadoEm: new DateTimeImmutable($linha['criado_em']),
            atualizadoEm: $linha['atualizado_em'] ? new DateTimeImmutable($linha['atualizado_em']) : null,
            excluidoEm: $linha['excluido_em'] ? new DateTimeImmutable($linha['excluido_em']) : null,
        );
    }
}
