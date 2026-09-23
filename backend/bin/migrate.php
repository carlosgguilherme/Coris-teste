<?php

declare(strict_types=1);

use App\Infrastructure\Config\Env;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Migrator;

require __DIR__ . '/../vendor/autoload.php';

$raiz = dirname(__DIR__);
Env::load("{$raiz}/.env");

(new Migrator(ConnectionFactory::fromEnv($raiz), "{$raiz}/database/schema"))->migrar();

echo 'Banco de dados atualizado.' . PHP_EOL;
