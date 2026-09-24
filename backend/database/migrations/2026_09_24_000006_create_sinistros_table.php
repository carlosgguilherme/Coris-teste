<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinistros', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('apolice_id')->constrained('apolices');
            $table->string('cobertura', 30);
            $table->date('data_ocorrencia');
            $table->date('data_aviso');
            $table->unsignedInteger('valor_reclamado_centavos');
            $table->unsignedInteger('valor_pago_centavos')->default(0);
            $table->string('status', 20);
            $table->string('motivo_negativa', 120)->nullable();
            $table->timestamps();

            $table->index(['status', 'data_aviso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinistros');
    }
};
