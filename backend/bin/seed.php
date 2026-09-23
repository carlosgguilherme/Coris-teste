<?php

declare(strict_types=1);

$service = (require __DIR__ . '/../bootstrap/app.php')->apoliceService();

$hoje = new DateTimeImmutable('today');
$data = fn (string $modificador) => $hoje->modify($modificador)->format('Y-m-d');

$apolices = [
    ['Mariana Alves Costa', '529.982.247-25', 'mariana.costa@email.com', '1990-04-12', 'europa', 'plus', $data('+10 days'), $data('+24 days')],
    ['João Pedro Santos', '111.444.777-35', 'joao.santos@email.com', '1958-09-30', 'america_do_norte', 'premium', $data('+30 days'), $data('+44 days')],
    ['Ana Beatriz Lima', '390.533.447-05', 'ana.lima@email.com', '2001-01-22', 'america_do_sul', 'essencial', $data('+5 days'), $data('+11 days')],
    ['Ricardo Menezes', '168.995.350-09', 'ricardo.menezes@email.com', '1985-07-03', 'asia', 'premium', $data('+60 days'), $data('+80 days')],
    ['Fernanda Rocha', '714.602.380-01', 'fernanda.rocha@email.com', '1976-11-18', 'nacional', 'essencial', $data('+2 days'), $data('+6 days')],
];

foreach ($apolices as [$nome, $cpf, $email, $nascimento, $destino, $plano, $inicio, $fim]) {
    $apolice = $service->criar([
        'seguradoNome' => $nome,
        'seguradoCpf' => $cpf,
        'seguradoEmail' => $email,
        'seguradoNascimento' => $nascimento,
        'destino' => $destino,
        'plano' => $plano,
        'inicioVigencia' => $inicio,
        'fimVigencia' => $fim,
    ]);

    echo "Apólice {$apolice->numero()} emitida para {$nome}." . PHP_EOL;
}
