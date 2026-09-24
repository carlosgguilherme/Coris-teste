<?php

namespace Database\Seeders;

use App\Models\Canal;
use Illuminate\Database\Seeder;

class CanaisSeeder extends Seeder
{
    public const CANAIS = [
        'site' => 'Site',
        'agencia' => 'Agências de viagem',
        'corretor' => 'Corretores',
        'parceiro' => 'Parceiros',
        'app' => 'App',
    ];

    public function run(): void
    {
        foreach (self::CANAIS as $codigo => $nome) {
            Canal::updateOrCreate(['codigo' => $codigo], ['nome' => $nome]);
        }
    }
}
