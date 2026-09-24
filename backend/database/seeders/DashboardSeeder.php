<?php

namespace Database\Seeders;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Enums\StatusCotacao;
use App\Models\Canal;
use App\Rules\Cpf;
use App\Services\Premio\CalculadoraPremio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Histórico de 24 meses para as dashboards: campanhas, apólices, cotações com o funil,
 * sinistros e atendimentos. Usa semente fixa, então gera sempre os mesmos dados.
 */
class DashboardSeeder extends Seeder
{
    private const SEMENTE = 2026;

    private const MESES = 24;

    private const APOLICES_POR_MES = 125;

    private const CRESCIMENTO_ANUAL = 0.12;

    private const SEGURADOS = 1800;

    /** Peso de cada mês nas vendas (1 = mês normal). */
    private const SAZONALIDADE = [1 => 1.3, 1.1, 0.8, 0.8, 0.85, 1.15, 1.45, 0.9, 0.85, 0.95, 1.0, 1.4];

    private const DESTINOS = ['europa' => 38, 'america_do_norte' => 25, 'america_do_sul' => 20, 'asia' => 7, 'nacional' => 6, 'oceania' => 3, 'africa' => 1];

    private const PLANOS = ['essencial' => 35, 'plus' => 45, 'premium' => 20];

    private const CANAIS = ['site' => 40, 'agencia' => 30, 'corretor' => 18, 'parceiro' => 8, 'app' => 4];

    /** Cotações que viram apólice, por canal (no site, o celular converte menos). */
    private const CONVERSAO = ['agencia' => 0.35, 'corretor' => 0.32, 'parceiro' => 0.28, 'app' => 0.22, 'site_desktop' => 0.20, 'site_mobile' => 0.13];

    /** Em qual etapa o cliente desiste, entre as cotações abandonadas. */
    private const ETAPA_ABANDONO = ['iniciada' => 30, 'calculada' => 35, 'dados_preenchidos' => 20, 'pagamento' => 15];

    /** Dias entre a compra e o início da viagem. */
    private const ANTECEDENCIA = [[1, 6, 20], [7, 15, 20], [16, 30, 25], [31, 60, 20], [61, 120, 15]];

    /** [frequência de sinistros, sinistralidade desejada] por destino. */
    private const RISCO = [
        'america_do_norte' => [0.09, 0.85], 'europa' => [0.06, 0.55], 'asia' => [0.06, 0.60], 'oceania' => [0.06, 0.55],
        'africa' => [0.06, 0.50], 'america_do_sul' => [0.06, 0.45], 'nacional' => [0.04, 0.40],
    ];

    /** Peso da cobertura no sorteio e fator do valor (despesa médica custa mais que atraso de voo). */
    private const COBERTURAS = ['despesas_medicas' => [55, 1.3], 'bagagem' => [15, 0.5], 'cancelamento' => [12, 1.0], 'atraso_voo' => [10, 0.3], 'odontologica' => [8, 0.4]];

    private const CAMPANHAS = [
        ['Carnaval', '01-20', '02-28', 'instagram'],
        ['Intercâmbio', '03-10', '04-30', 'google'],
        ['Férias de Julho', '06-01', '07-31', 'meta'],
        ['Primavera na Europa', '09-01', '09-30', 'email'],
        ['Black Friday', '11-18', '12-01', 'google'],
        ['Réveillon', '12-02', '12-31', 'tiktok'],
    ];

    private const NOMES = ['Ana', 'Bruno', 'Camila', 'Daniel', 'Eduarda', 'Felipe', 'Gabriela', 'Henrique', 'Isabela', 'João', 'Larissa', 'Lucas', 'Mariana', 'Matheus', 'Natália', 'Otávio', 'Patrícia', 'Rafael', 'Sofia', 'Thiago', 'Vanessa', 'Vinícius', 'Beatriz', 'Gustavo', 'Juliana', 'Pedro', 'Renata', 'Ricardo', 'Tatiana', 'Carlos'];

    private const SOBRENOMES = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins', 'Carvalho', 'Almeida', 'Rocha', 'Barbosa', 'Moreira', 'Mendes', 'Cardoso'];

    private const TIPOS_ATENDIMENTO = ['Orientação médica', 'Pedido de reembolso', 'Extravio de bagagem', 'Dúvida sobre cobertura', 'Segunda via do voucher'];

    private const MOTIVOS_NEGATIVA = ['Doença preexistente', 'Documentação incompleta', 'Evento fora da vigência', 'Cobertura não contratada'];

    private int $sequenciaSinistro = 0;

    public function __construct(private readonly CalculadoraPremio $calculadora) {}

    public function run(): void
    {
        if (DB::table('cotacoes')->exists()) {
            $this->command?->warn('Os dados da dashboard já existem. Para gerar de novo: php artisan migrate:fresh --seed');

            return;
        }

        // Tudo ou nada: se algo falhar no meio, nenhum dado fica pela metade
        DB::transaction(fn () => $this->gerar());
    }

    private function gerar(): void
    {
        mt_srand(self::SEMENTE);

        $canais = Canal::pluck('id', 'codigo');
        $campanhas = $this->criarCampanhas();
        $segurados = $this->criarSegurados();

        $apolices = [];
        $cotacoes = [];
        $proximaApolice = (int) DB::table('apolices')->max('id') + 1;

        foreach ($this->emissoes() as $emissao) {
            $canal = $this->sortear(self::CANAIS);
            $device = $this->device($canal);
            $campanha = $this->campanhaDaVenda($campanhas, $canal, $emissao);
            $apolice = $this->novaApolice($proximaApolice++, $emissao, $segurados, $canais[$canal], $campanha);
            $apolices[] = $apolice;

            $contexto = ['canal_id' => $canais[$canal], 'campanha' => $campanha, 'device' => $device, 'data' => $emissao];
            $cotacoes[] = $this->novaCotacao($contexto, StatusCotacao::Convertida, $apolice);
            foreach (range(1, $this->abandonosPorVenda($canal, $device)) as $_) {
                $cotacoes[] = $this->novaCotacao($contexto, StatusCotacao::Abandonada);
            }
        }

        $this->inserir('apolices', $apolices);
        $this->inserirCotacoes($cotacoes);
        $this->inserir('sinistros', $this->gerarSinistros($apolices));
        $this->inserir('atendimentos', $this->gerarAtendimentos($apolices));
        $this->atualizarInvestimentoDasCampanhas($campanhas);

        $this->command?->info(sprintf('Dashboard: %d apólices, %d cotações, %d segurados.', count($apolices), count($cotacoes), count($segurados)));
    }

    /** @return list<array> campanhas dos últimos 24 meses que já começaram */
    private function criarCampanhas(): array
    {
        $limite = now()->subMonths(self::MESES);
        $campanhas = [];

        foreach (range(now()->year - 2, now()->year) as $ano) {
            foreach (self::CAMPANHAS as [$nome, $inicio, $fim, $utm]) {
                $inicioCampanha = Carbon::parse("{$ano}-{$inicio}");
                $fimCampanha = Carbon::parse("{$ano}-{$fim}");
                if ($fimCampanha->lt($limite) || $inicioCampanha->gt(now())) {
                    continue;
                }

                $campanhas[] = [
                    'nome' => "{$nome} {$ano}",
                    'utm_source' => $utm,
                    'utm_campaign' => str($nome)->slug('_')."_{$ano}",
                    'orcamento_centavos' => mt_rand(400, 1200) * 1000,
                    'investimento_centavos' => 0,
                    'inicio' => $inicioCampanha->toDateString(),
                    'fim' => $fimCampanha->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('campanhas')->insert($campanhas);

        return DB::table('campanhas')->get()->map(fn ($c) => (array) $c)->all();
    }

    /** @return list<array{id: int, nascimento: string}> */
    private function criarSegurados(): array
    {
        $cpfsExistentes = DB::table('segurados')->pluck('cpf')->flip();
        $proximoId = (int) DB::table('segurados')->max('id') + 1;
        $linhas = [];

        while (count($linhas) < self::SEGURADOS) {
            $cpf = $this->cpfValido();
            if (isset($cpfsExistentes[$cpf])) {
                continue;
            }
            $cpfsExistentes[$cpf] = true;

            $nome = self::NOMES[mt_rand(0, count(self::NOMES) - 1)].' '.self::SOBRENOMES[mt_rand(0, count(self::SOBRENOMES) - 1)];
            $linhas[] = [
                'id' => $proximoId++,
                'nome' => $nome,
                'cpf' => $cpf,
                'email' => str($nome)->ascii()->lower()->replace(' ', '.').mt_rand(1, 999).'@email.com',
                'data_nascimento' => now()->subYears($this->idade())->subDays(mt_rand(0, 364))->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->inserir('segurados', $linhas);

        return array_map(fn ($linha) => ['id' => $linha['id'], 'nascimento' => $linha['data_nascimento']], $linhas);
    }

    /** Datas de emissão das apólices, mês a mês, com sazonalidade e crescimento anual. */
    private function emissoes(): \Generator
    {
        foreach (range(self::MESES - 1, 0) as $mesesAtras) {
            $mes = now()->startOfMonth()->subMonths($mesesAtras);
            $ultimoDia = $mesesAtras === 0 ? now()->day : $mes->daysInMonth;
            $crescimento = (1 + self::CRESCIMENTO_ANUAL) ** (-$mesesAtras / 12);
            $quantidade = (int) round(self::APOLICES_POR_MES * self::SAZONALIDADE[$mes->month] * $crescimento * $ultimoDia / $mes->daysInMonth);

            foreach (range(1, max($quantidade, 1)) as $_) {
                $emissao = $mes->copy()->addDays(mt_rand(0, $ultimoDia - 1))->setTime(mt_rand(8, 23), mt_rand(0, 59));

                yield $emissao->gt(now()) ? now()->subMinutes(mt_rand(5, 300)) : $emissao;
            }
        }
    }

    private function novaApolice(int $id, Carbon $emissao, array $segurados, int $canalId, ?array $campanha): array
    {
        $destino = $this->sortear(self::DESTINOS);
        $plano = $destino === 'nacional' ? 'essencial' : $this->sortear(self::PLANOS);
        [$de, $ate] = $this->faixa(self::ANTECEDENCIA);
        $inicio = $emissao->copy()->startOfDay()->addDays(mt_rand($de, $ate));
        $fim = $inicio->copy()->addDays($this->diasDeViagem() - 1);
        $segurado = $segurados[mt_rand(0, count($segurados) - 1)];

        return [
            'id' => $id,
            'numero' => sprintf('CRS-%d-%08X', $emissao->year, mt_rand(0, 0x7FFFFFFF)),
            'segurado_id' => $segurado['id'],
            'canal_id' => $canalId,
            'campanha_id' => $campanha['id'] ?? null,
            'destino' => $destino,
            'plano' => $plano,
            'inicio_vigencia' => $inicio->toDateString(),
            'fim_vigencia' => $fim->toDateString(),
            'valor_premio_centavos' => $this->calculadora->calcular(
                Plano::from($plano), Destino::from($destino), $inicio, $fim, Carbon::parse($segurado['nascimento']),
            ),
            'status' => mt_rand(1, 100) <= 4 ? 'cancelada' : 'ativa',
            'created_at' => $emissao,
            'updated_at' => $emissao,
        ];
    }

    private function novaCotacao(array $contexto, StatusCotacao $status, ?array $apolice = null): array
    {
        $destino = $apolice['destino'] ?? $this->sortear(self::DESTINOS);
        $plano = $apolice['plano'] ?? $this->sortear(self::PLANOS);
        $dias = $apolice ? Carbon::parse($apolice['inicio_vigencia'])->diffInDays($apolice['fim_vigencia']) + 1 : $this->diasDeViagem();
        $criadaEm = $apolice
            ? $contexto['data']->copy()->subMinutes(mt_rand(5, 90))
            : $contexto['data']->copy()->subDays(mt_rand(0, 20))->subMinutes(mt_rand(0, 600));

        return [
            'codigo' => $this->uuid(),
            'canal_id' => $contexto['canal_id'],
            'campanha_id' => $contexto['campanha']['id'] ?? null,
            'apolice_id' => $apolice['id'] ?? null,
            'destino' => $destino,
            'plano' => $plano,
            'dias' => $dias,
            'valor_calculado_centavos' => $apolice['valor_premio_centavos'] ?? $this->calculadora->calcular(
                Plano::from($plano), Destino::from($destino), now(), now()->addDays($dias - 1), now()->subYears(35),
            ),
            'device' => $contexto['device'],
            'status' => $status->value,
            'etapa_abandono' => $status === StatusCotacao::Abandonada ? $this->sortear(self::ETAPA_ABANDONO) : null,
            'created_at' => $criadaEm,
            'updated_at' => $criadaEm,
            'utm_source' => $contexto['campanha']['utm_source'] ?? null,
        ];
    }

    /** Grava as cotações e um evento para cada etapa do funil que o cliente passou. */
    private function inserirCotacoes(array $cotacoes): void
    {
        $proximoId = (int) DB::table('cotacoes')->max('id') + 1;
        $etapas = array_map(fn (StatusCotacao $etapa) => $etapa->value, StatusCotacao::etapasDoFunil());
        $eventos = [];

        foreach ($cotacoes as $indice => $cotacao) {
            $cotacoes[$indice]['id'] = $proximoId++;
            $ultimaEtapa = $cotacao['etapa_abandono'] ?? StatusCotacao::Convertida->value;

            foreach (array_slice($etapas, 0, array_search($ultimaEtapa, $etapas) + 1) as $ordem => $etapa) {
                $eventos[] = [
                    'cotacao_id' => $cotacoes[$indice]['id'],
                    'etapa' => $etapa,
                    'utm_source' => $cotacao['utm_source'],
                    'device' => $cotacao['device'],
                    'ocorrido_em' => $cotacao['created_at']->copy()->addMinutes($ordem * 2),
                ];
            }
            unset($cotacoes[$indice]['utm_source']);
        }

        $this->inserir('cotacoes', $cotacoes);
        $this->inserir('funil_eventos', $eventos);
    }

    private function gerarSinistros(array $apolices): array
    {
        $sinistros = [];

        foreach ($apolices as $apolice) {
            $inicio = Carbon::parse($apolice['inicio_vigencia']);
            [$frequencia, $sinistralidade] = self::RISCO[$apolice['destino']];
            if ($apolice['status'] === 'cancelada' || $inicio->gte(today()) || mt_rand() / mt_getrandmax() > $frequencia) {
                continue;
            }

            $ultimoDia = min(Carbon::parse($apolice['fim_vigencia'])->timestamp, today()->timestamp);
            $ocorrencia = Carbon::createFromTimestamp(mt_rand($inicio->timestamp, $ultimoDia))->startOfDay();
            $aviso = $ocorrencia->copy()->addDays(mt_rand(0, 10))->min(today());
            $cobertura = $this->sortear(array_map(fn ($c) => $c[0], self::COBERTURAS));
            $valorMedio = $apolice['valor_premio_centavos'] * $sinistralidade / ($frequencia * 0.85);
            $reclamado = (int) ($valorMedio * self::COBERTURAS[$cobertura][1] * mt_rand(60, 140) / 100);

            $sinistros[] = [
                'numero' => sprintf('SIN-%d-%06d', $aviso->year, ++$this->sequenciaSinistro),
                'apolice_id' => $apolice['id'],
                'cobertura' => $cobertura,
                'data_ocorrencia' => $ocorrencia->toDateString(),
                'data_aviso' => $aviso->toDateString(),
                'valor_reclamado_centavos' => $reclamado,
                ...$this->situacaoDoSinistro($aviso, $reclamado),
                'created_at' => $aviso,
                'updated_at' => $aviso,
            ];
        }

        return $sinistros;
    }

    private function situacaoDoSinistro(Carbon $aviso, int $reclamado): array
    {
        $diasDesdeAviso = $aviso->diffInDays(today());
        $negado = mt_rand(1, 100) <= 10;

        $status = match (true) {
            $diasDesdeAviso <= 7 => 'aberto',
            $diasDesdeAviso <= 30 => mt_rand(0, 1) ? 'em_analise' : 'aprovado',
            $negado => 'negado',
            default => 'pago',
        };

        return [
            'status' => $status,
            'valor_pago_centavos' => $status === 'pago' ? (int) ($reclamado * mt_rand(85, 100) / 100) : 0,
            'motivo_negativa' => $status === 'negado' ? self::MOTIVOS_NEGATIVA[mt_rand(0, count(self::MOTIVOS_NEGATIVA) - 1)] : null,
        ];
    }

    private function gerarAtendimentos(array $apolices): array
    {
        $atendimentos = [];

        foreach ($apolices as $apolice) {
            $inicio = Carbon::parse($apolice['inicio_vigencia']);
            if ($inicio->gte(today()) || mt_rand(1, 100) > 45) {
                continue;
            }

            $ultimoDia = min(Carbon::parse($apolice['fim_vigencia'])->endOfDay()->timestamp, now()->timestamp);
            foreach (range(1, mt_rand(1, 2)) as $_) {
                $canal = $this->sortear(['telefone' => 45, 'whatsapp' => 40, 'app' => 15]);
                $espera = match ($canal) {
                    'telefone' => mt_rand(30, 420),
                    'whatsapp' => mt_rand(10, 240),
                    'app' => mt_rand(5, 120),
                };
                $quando = Carbon::createFromTimestamp(mt_rand($inicio->timestamp, $ultimoDia));

                $atendimentos[] = [
                    'apolice_id' => $apolice['id'],
                    'canal' => $canal,
                    'tipo' => self::TIPOS_ATENDIMENTO[mt_rand(0, count(self::TIPOS_ATENDIMENTO) - 1)],
                    'inicio' => $quando,
                    'tempo_espera_seg' => $espera,
                    'dentro_sla' => $espera <= 180,
                    'nps' => mt_rand(1, 100) <= 70 ? $this->notaNps($espera) : null,
                    'created_at' => $quando,
                    'updated_at' => $quando,
                ];
            }
        }

        return $atendimentos;
    }

    /** Quem espera muito tende a dar nota menor. */
    private function notaNps(int $espera): int
    {
        $sorteio = mt_rand(1, 100) + ($espera > 180 ? 12 : 0);

        return match (true) {
            $sorteio <= 62 => mt_rand(9, 10),
            $sorteio <= 86 => mt_rand(7, 8),
            default => mt_rand(0, 6),
        };
    }

    /** Investimento = orçamento gasto (proporcional ao tempo, se a campanha ainda está no ar). */
    private function atualizarInvestimentoDasCampanhas(array $campanhas): void
    {
        foreach ($campanhas as $campanha) {
            $inicio = Carbon::parse($campanha['inicio']);
            $fim = Carbon::parse($campanha['fim']);
            $decorrido = min(1, ($inicio->diffInDays(today()) + 1) / ($inicio->diffInDays($fim) + 1));

            DB::table('campanhas')->where('id', $campanha['id'])->update([
                'investimento_centavos' => (int) ($campanha['orcamento_centavos'] * $decorrido * mt_rand(88, 100) / 100),
            ]);
        }
    }

    private function campanhaDaVenda(array $campanhas, string $canal, Carbon $emissao): ?array
    {
        if (! in_array($canal, ['site', 'app']) || mt_rand(1, 100) > 60) {
            return null;
        }

        foreach ($campanhas as $campanha) {
            if ($emissao->between(Carbon::parse($campanha['inicio']), Carbon::parse($campanha['fim'])->endOfDay())) {
                return $campanha;
            }
        }

        return null;
    }

    private function abandonosPorVenda(string $canal, string $device): int
    {
        $conversao = self::CONVERSAO[$canal === 'site' ? "site_{$device}" : $canal];
        $media = (1 - $conversao) / $conversao;
        $inteiro = (int) floor($media);

        return max(1, $inteiro + (mt_rand() / mt_getrandmax() < $media - $inteiro ? 1 : 0));
    }

    private function device(string $canal): string
    {
        return match ($canal) {
            'app' => 'mobile',
            'site' => mt_rand(1, 100) <= 60 ? 'mobile' : 'desktop',
            default => 'desktop',
        };
    }

    private function diasDeViagem(): int
    {
        [$de, $ate] = $this->faixa([[3, 7, 30], [8, 15, 40], [16, 30, 20], [31, 90, 10]]);

        return mt_rand($de, $ate);
    }

    private function idade(): int
    {
        [$de, $ate] = $this->faixa([[18, 30, 25], [31, 45, 35], [46, 59, 22], [60, 74, 14], [75, 84, 4]]);

        return mt_rand($de, $ate);
    }

    /** Sorteia uma chave do array respeitando os pesos. */
    private function sortear(array $pesos): string
    {
        $sorteio = mt_rand(1, array_sum($pesos));
        foreach ($pesos as $chave => $peso) {
            $sorteio -= $peso;
            if ($sorteio <= 0) {
                return $chave;
            }
        }

        return array_key_last($pesos);
    }

    /** Sorteia uma faixa [de, até, peso] e devolve [de, até]. */
    private function faixa(array $faixas): array
    {
        $indice = (int) $this->sortear(array_map(fn ($faixa) => $faixa[2], $faixas));

        return [$faixas[$indice][0], $faixas[$indice][1]];
    }

    private function cpfValido(): string
    {
        do {
            $base = '';
            foreach (range(1, 9) as $_) {
                $base .= mt_rand(0, 9);
            }
            foreach ([10, 11] as $peso) {
                $soma = 0;
                foreach (str_split($base) as $i => $digito) {
                    $soma += (int) $digito * ($peso - $i);
                }
                $base .= ((10 * $soma) % 11) % 10;
            }
        } while (! Cpf::valido($base));

        return $base;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-4%03x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFF),
            mt_rand(0x8000, 0xBFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF));
    }

    private function inserir(string $tabela, array $linhas): void
    {
        foreach (array_chunk($linhas, 250) as $lote) {
            DB::table($tabela)->insert($lote);
        }
    }
}
