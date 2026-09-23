<?php

declare(strict_types=1);

use App\Http\Request;

$container = require __DIR__ . '/../bootstrap/app.php';

$container->kernel()->handle(Request::fromGlobals())->send();
