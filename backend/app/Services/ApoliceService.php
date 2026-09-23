<?php

namespace App\Services;

use App\Enums\Destino;
use App\Enums\Plano;
use App\Enums\StatusApolice;
use App\Models\Apolice;
use App\Models\Segurado;
use App\Services\Premio\CalculadoraPremio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApoliceService
{
    public const POR_PAGINA = 10;

    public function __construct(private readonly CalculadoraPremio $calculadora)
    {
    }

    public function listar(?string $busca, ?string $status): LengthAwarePaginator
    {
        return Apolice::with('segurado')
            ->buscar($busca)
            ->when(StatusApolice::tryFrom((string) $status), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->latest('id')
            ->paginate(self::POR_PAGINA);
    }

    public function resumo(): array
    {
        $ativas = Apolice::where('status', StatusApolice::Ativa);

        return [
            'total' => Apolice::count(),
            'ativas' => $ativas->count(),
            'premioAtivasCentavos' => (int) $ativas->sum('valor_premio_centavos'),
        ];
    }

    public function criar(array $dados): Apolice
    {
        return DB::transaction(function () use ($dados) {
            $apolice = Apolice::create([
                ...$this->dadosDaApolice($dados),
                'numero' => $this->gerarNumero(),
                'segurado_id' => $this->salvarSegurado($dados)->id,
                'status' => StatusApolice::Ativa,
            ]);

            return $apolice->load('segurado');
        });
    }

    public function atualizar(Apolice $apolice, array $dados): Apolice
    {
        $novoStatus = StatusApolice::tryFrom($dados['status'] ?? '') ?? $apolice->status;

        if ($apolice->status === StatusApolice::Cancelada && $novoStatus === StatusApolice::Cancelada) {
            throw ValidationException::withMessages([
                'status' => 'Apólice cancelada não pode ser alterada. Reative-a primeiro.',
            ]);
        }

        return DB::transaction(function () use ($apolice, $dados, $novoStatus) {
            $apolice->update([
                ...$this->dadosDaApolice($dados),
                'segurado_id' => $this->salvarSegurado($dados)->id,
                'status' => $novoStatus,
            ]);

            return $apolice->load('segurado');
        });
    }

    public function excluir(Apolice $apolice): void
    {
        $apolice->delete();
    }

    public function cotar(array $dados): array
    {
        $inicio = Carbon::parse($dados['inicioVigencia']);
        $fim = Carbon::parse($dados['fimVigencia']);

        return [
            'valorPremioCentavos' => $this->premio($dados),
            'dias' => (int) $inicio->diffInDays($fim) + 1,
        ];
    }

    private function dadosDaApolice(array $dados): array
    {
        return [
            'destino' => $dados['destino'],
            'plano' => $dados['plano'],
            'inicio_vigencia' => $dados['inicioVigencia'],
            'fim_vigencia' => $dados['fimVigencia'],
            'valor_premio_centavos' => $this->premio($dados),
        ];
    }

    private function salvarSegurado(array $dados): Segurado
    {
        return Segurado::updateOrCreate(
            ['cpf' => preg_replace('/\D/', '', $dados['seguradoCpf'])],
            [
                'nome' => trim($dados['seguradoNome']),
                'email' => mb_strtolower(trim($dados['seguradoEmail'])),
                'data_nascimento' => $dados['seguradoNascimento'],
            ],
        );
    }

    private function premio(array $dados): int
    {
        return $this->calculadora->calcular(
            Plano::from($dados['plano']),
            Destino::from($dados['destino']),
            Carbon::parse($dados['inicioVigencia']),
            Carbon::parse($dados['fimVigencia']),
            Carbon::parse($dados['seguradoNascimento']),
        );
    }

    private function gerarNumero(): string
    {
        do {
            $numero = 'CRS-'.now()->year.'-'.Str::upper(bin2hex(random_bytes(4)));
        } while (Apolice::withTrashed()->where('numero', $numero)->exists());

        return $numero;
    }
}
