<?php

use App\Modules\Estoque\Http\Controllers\EntradaController;
use App\Modules\Estoque\Http\Controllers\EstoqueController;
use App\Modules\Estoque\Http\Controllers\SaidaController;
use Illuminate\Support\Facades\Route;

// As rotas GET de exibição (entradas, entradas/nova, entradas/{entrada}/editar,
// saidas, saidas/nova, estoque, estoque/{estoque}/editar) são registradas
// automaticamente pelas respectivas Filament Pages — aqui só ficam as ações
// de escrita (POST/PUT), que continuam como Controllers comuns.
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('entradas', [EntradaController::class, 'store'])->name('entradas.store');
    Route::put('entradas/{entrada}', [EntradaController::class, 'update'])->name('entradas.update');
    Route::post('entradas/{entrada}/cancelar', [EntradaController::class, 'cancelar'])->name('entradas.cancelar');
    Route::post('saidas', [SaidaController::class, 'store'])->name('saidas.store');
    Route::put('estoque/{estoque}', [EstoqueController::class, 'update'])->name('estoque.update');
});
