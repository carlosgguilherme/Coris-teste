<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funil_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotacao_id')->constrained('cotacoes')->cascadeOnDelete();
            $table->string('etapa', 20);
            $table->string('utm_source', 30)->nullable();
            $table->string('device', 10);
            $table->timestamp('ocorrido_em');

            $table->index(['cotacao_id', 'etapa']);
            $table->index('ocorrido_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funil_eventos');
    }
};
