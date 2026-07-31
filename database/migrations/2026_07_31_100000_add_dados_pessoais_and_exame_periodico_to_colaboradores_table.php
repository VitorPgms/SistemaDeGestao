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
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->date('data_nascimento')->nullable()->after('nome');
            $table->string('tamanho_vestimenta')->nullable()->after('funcao');
            $table->date('data_ultimo_exame_periodico')->nullable()->after('data_demissao');
            $table->date('data_proximo_exame_periodico')->nullable()->after('data_ultimo_exame_periodico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'data_nascimento',
                'tamanho_vestimenta',
                'data_ultimo_exame_periodico',
                'data_proximo_exame_periodico',
            ]);
        });
    }
};
