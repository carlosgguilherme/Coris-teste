<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apolice_id')->nullable()->constrained('apolices');
            $table->string('canal', 20);
            $table->string('tipo', 40);
            $table->timestamp('inicio');
            $table->unsignedInteger('tempo_espera_seg');
            $table->boolean('dentro_sla');
            $table->unsignedTinyInteger('nps')->nullable();
            $table->timestamps();

            $table->index('inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos');
    }
};
