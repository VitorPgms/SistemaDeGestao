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
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cd_id')->constrained('centros_distribuicao')->restrictOnDelete();
            $table->foreignId('setor_id')->constrained('setores')->restrictOnDelete();
            $table->string('nome');
            $table->string('funcao');
            $table->date('data_admissao');
            $table->date('data_demissao')->nullable();
            $table->string('status')->default('ativo');
            $table->timestamps();

            $table->index(['cd_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};
