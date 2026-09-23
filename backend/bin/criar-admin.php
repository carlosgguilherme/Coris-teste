<?php

declare(strict_types=1);

use App\Domain\Usuario\Usuario;
use App\Infrastructure\Config\Env;

$usuarios = (require __DIR__ . '/../bootstrap/app.php')->usuarios();

if ($usuarios->existeAlgum()) {
    echo 'Usuário administrador já existe.' . PHP_EOL;
    return;
}

$email = Env::get('ADMIN_EMAIL', 'admin@seguroviagem.com');
$usuarios->salvar(Usuario::cadastrar(
    Env::get('ADMIN_NOME', 'Administrador'),
    $email,
    Env::get('ADMIN_SENHA', 'Admin@123'),
));

echo "Usuário {$email} criado." . PHP_EOL;
