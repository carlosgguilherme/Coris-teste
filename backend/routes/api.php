<?php

use App\Http\Controllers\Api\ApoliceController;
use App\Http\Controllers\Api\OpcoesController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);
Route::get('/opcoes', OpcoesController::class);

Route::get('/apolices/resumo', [ApoliceController::class, 'resumo']);
Route::post('/apolices/cotacao', [ApoliceController::class, 'cotacao']);
Route::apiResource('apolices', ApoliceController::class)
    ->parameters(['apolices' => 'apolice'])
    ->whereNumber('apolice');
