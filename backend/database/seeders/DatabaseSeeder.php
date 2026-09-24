<?php

namespace Database\Seeders;

use App\Services\ApoliceService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(ApoliceService $service): void
    {
        $this->call([CanaisSeeder::class, DashboardSeeder::class]);

        $data = fn (int $dias) => today()->addDays($dias)->toDateString();

        $apolices = [
            ['Mariana Alves Costa', '529.982.247-25', 'mariana.costa@email.com', '1990-04-12', 'europa', 'plus', 10, 24],
            ['João Pedro Santos', '111.444.777-35', 'joao.santos@email.com', '1958-09-30', 'america_do_norte', 'premium', 30, 44],
            ['Ana Beatriz Lima', '390.533.447-05', 'ana.lima@email.com', '2001-01-22', 'america_do_sul', 'essencial', 5, 11],
            ['Ricardo Menezes', '168.995.350-09', 'ricardo.menezes@email.com', '1985-07-03', 'asia', 'premium', 60, 80],
            ['Fernanda Rocha', '714.602.380-01', 'fernanda.rocha@email.com', '1976-11-18', 'nacional', 'essencial', 2, 6],
            ['Mariana Alves Costa', '529.982.247-25', 'mariana.costa@email.com', '1990-04-12', 'nacional', 'essencial', 90, 93],
        ];

        foreach ($apolices as [$nome, $cpf, $email, $nascimento, $destino, $plano, $inicio, $fim]) {
            $apolice = $service->criar([
                'seguradoNome' => $nome,
                'seguradoCpf' => $cpf,
                'seguradoEmail' => $email,
                'seguradoNascimento' => $nascimento,
                'destino' => $destino,
                'plano' => $plano,
                'inicioVigencia' => $data($inicio),
                'fimVigencia' => $data($fim),
            ]);

            $this->command->info("Apólice {$apolice->numero} emitida para {$nome}.");
        }
    }
}
