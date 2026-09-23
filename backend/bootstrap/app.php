<?php

declare(strict_types=1);

use App\Container;
use App\Infrastructure\Config\Env;
use App\Infrastructure\Database\ConnectionFactory;

require_once __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));

return new Container(ConnectionFactory::fromEnv(dirname(__DIR__)));
