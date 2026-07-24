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
        Schema::create('saidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cd_id')->constrained('centros_distribuicao')->restrictOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->foreignId('produto_variacao_id')->nullable()->constrained('produto_variacoes')->restrictOnDelete();
            $table->unsignedInteger('quantidade');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->foreignId('liberado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('motivo_saida_id')->constrained('motivos_saida')->restrictOnDelete();
            $table->string('status_colaborador_snapshot');
            $table->date('data');
            $table->time('hora');
            $table->text('observacoes')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->nullableMorphs('origem');
            $table->timestamps();

            $table->index(['cd_id', 'produto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saidas');
    }
};
