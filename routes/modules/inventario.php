<?php

use App\Modules\Inventario\Http\Controllers\InventarioController;
use Illuminate\Support\Facades\Route;

// As rotas GET de exibição (inventarios, inventarios/novo,
// inventarios/{inventario}) são registradas automaticamente pelas
// respectivas Filament Pages — aqui só ficam as ações de escrita.
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('inventarios', [InventarioController::class, 'store'])->name('inventarios.store');
    Route::put('inventarios/{inventario}/contagem', [InventarioController::class, 'salvarContagem'])->name('inventarios.contagem');
    Route::post('inventarios/{inventario}/finalizar', [InventarioController::class, 'finalizar'])->name('inventarios.finalizar');
    Route::post('inventarios/{inventario}/cancelar', [InventarioController::class, 'cancelar'])->name('inventarios.cancelar');
});
