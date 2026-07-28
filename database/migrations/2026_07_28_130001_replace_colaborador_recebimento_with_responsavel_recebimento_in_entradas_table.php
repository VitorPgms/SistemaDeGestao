<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->dropForeign(['colaborador_recebimento_id']);
            $table->dropColumn('colaborador_recebimento_id');

            $table->foreignId('responsavel_recebimento_id')
                ->nullable()
                ->after('numero_nota_fiscal')
                ->constrained('responsaveis_recebimento')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->dropForeign(['responsavel_recebimento_id']);
            $table->dropColumn('responsavel_recebimento_id');

            $table->foreignId('colaborador_recebimento_id')
                ->nullable()
                ->constrained('colaboradores')
                ->restrictOnDelete();
        });
    }
};
