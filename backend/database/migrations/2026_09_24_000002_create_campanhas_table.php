<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanhas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('utm_source', 30);
            $table->string('utm_campaign', 60);
            $table->unsignedInteger('orcamento_centavos');
            $table->unsignedInteger('investimento_centavos')->default(0);
            $table->date('inicio');
            $table->date('fim');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanhas');
    }
};
