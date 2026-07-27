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
        Schema::create('inventario_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_id')->constrained('inventarios')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->foreignId('produto_variacao_id')->nullable()->constrained('produto_variacoes')->restrictOnDelete();
            $table->unsignedInteger('quantidade_sistema');
            $table->unsignedInteger('quantidade_contada')->nullable();
            $table->timestamps();

            $table->unique(['inventario_id', 'produto_id', 'produto_variacao_id'], 'inventario_itens_produto_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_itens');
    }
};
