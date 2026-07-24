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
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cd_id')->constrained('centros_distribuicao')->restrictOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->foreignId('produto_variacao_id')->nullable()->constrained('produto_variacoes')->restrictOnDelete();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->restrictOnDelete();
            $table->string('numero_nota_fiscal');
            $table->date('data_compra');
            $table->date('data_entrega');
            $table->unsignedInteger('quantidade');
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('valor_total', 12, 2);
            $table->foreignId('colaborador_recebimento_id')->constrained('colaboradores')->restrictOnDelete();
            $table->text('observacoes')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            // Rastreabilidade de ajustes gerados por outros processos (ex: Inventário,
            // no Módulo 5) sem precisar de um caminho de mutação de estoque separado.
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
        Schema::dropIfExists('entradas');
    }
};
