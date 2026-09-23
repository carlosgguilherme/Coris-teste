<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apolices', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('segurado_id')->constrained('segurados');
            $table->string('destino', 30);
            $table->string('plano', 20);
            $table->date('inicio_vigencia');
            $table->date('fim_vigencia');
            $table->unsignedInteger('valor_premio_centavos');
            $table->string('status', 20)->default('ativa')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apolices');
    }
};
