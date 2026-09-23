<?php

namespace App\Providers;

use App\Services\Premio\CalculadoraPremio;
use App\Services\Premio\CalculadoraPremioViagem;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CalculadoraPremio::class, CalculadoraPremioViagem::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
