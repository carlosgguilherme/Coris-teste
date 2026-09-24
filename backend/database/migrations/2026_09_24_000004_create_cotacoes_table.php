<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotacoes', function (Blueprint $table) {
            $table->id();
            $table->uuid('codigo')->unique();
            $table->foreignId('canal_id')->constrained('canais');
            $table->foreignId('campanha_id')->nullable()->constrained('campanhas');
            $table->foreignId('apolice_id')->nullable()->constrained('apolices');
            $table->string('destino', 30);
            $table->string('plano', 20);
            $table->unsignedSmallInteger('dias');
            $table->unsignedInteger('valor_calculado_centavos');
            $table->string('device', 10);
            $table->string('status', 20);
            $table->string('etapa_abandono', 20)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotacoes');
    }
};
