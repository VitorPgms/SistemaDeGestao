<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Ajustes de estoque gerados pelo Inventário (Módulo 5) reaproveitam as
     * tabelas entradas/saidas via origem polimórfica, mas não têm fornecedor,
     * nota fiscal ou colaborador — esses campos precisam aceitar null.
     */
    public function up(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->foreignId('fornecedor_id')->nullable()->change();
            $table->string('numero_nota_fiscal')->nullable()->change();
            $table->foreignId('colaborador_recebimento_id')->nullable()->change();
        });

        Schema::table('saidas', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->nullable()->change();
            $table->string('status_colaborador_snapshot')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->foreignId('fornecedor_id')->nullable(false)->change();
            $table->string('numero_nota_fiscal')->nullable(false)->change();
            $table->foreignId('colaborador_recebimento_id')->nullable(false)->change();
        });

        Schema::table('saidas', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->nullable(false)->change();
            $table->string('status_colaborador_snapshot')->nullable(false)->change();
        });
    }
};
