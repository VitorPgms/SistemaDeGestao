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
        Schema::create('responsaveis_recebimento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cd_id')->constrained('centros_distribuicao')->restrictOnDelete();
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responsaveis_recebimento');
    }
};
