<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apolices', function (Blueprint $table) {
            $table->foreignId('canal_id')->nullable()->after('segurado_id')->constrained('canais');
            $table->foreignId('campanha_id')->nullable()->after('canal_id')->constrained('campanhas');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('apolices', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropConstrainedForeignId('campanha_id');
            $table->dropConstrainedForeignId('canal_id');
        });
    }
};
