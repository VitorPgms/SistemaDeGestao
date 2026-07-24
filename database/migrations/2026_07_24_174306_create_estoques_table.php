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
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cd_id')->constrained('centros_distribuicao')->restrictOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->foreignId('produto_variacao_id')->nullable()->constrained('produto_variacoes')->restrictOnDelete();
            $table->unsignedInteger('quantidade_atual')->default(0);
            $table->unsignedInteger('quantidade_minima')->default(0);
            $table->unsignedInteger('quantidade_ideal')->default(0);
            $table->string('localizacao')->nullable();
            $table->foreignId('fornecedor_preferencial_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->timestamp('ultima_entrada_at')->nullable();
            $table->timestamp('ultima_saida_at')->nullable();
            $table->timestamps();

            // produto_variacao_id é nullable, e o MySQL não considera dois NULLs
            // iguais em índices únicos — sem essa coluna normalizada, o mesmo
            // produto sem variação poderia gerar duas linhas de estoque para o
            // mesmo CD. A coluna gerada fecha essa brecha.
            $table->integer('produto_variacao_id_norm')
                ->storedAs('COALESCE(produto_variacao_id, 0)');

            $table->unique(['cd_id', 'produto_id', 'produto_variacao_id_norm'], 'estoques_cd_produto_variacao_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques');
    }
};
