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
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cd_id')->constrained('centros_distribuicao')->restrictOnDelete();
            $table->foreignId('responsavel_id')->constrained('users')->restrictOnDelete();
            $table->date('data_contagem');
            $table->string('status')->default('em_andamento');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['cd_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
