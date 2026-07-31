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
        Schema::table('saidas', function (Blueprint $table) {
            $table->string('status')->default('ativa')->after('hora');
            $table->text('motivo_cancelamento')->nullable()->after('observacoes');
            $table->foreignId('cancelado_por')->nullable()->after('motivo_cancelamento')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelado_em')->nullable()->after('cancelado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saidas', function (Blueprint $table) {
            $table->dropForeign(['cancelado_por']);
            $table->dropColumn(['status', 'motivo_cancelamento', 'cancelado_por', 'cancelado_em']);
        });
    }
};
