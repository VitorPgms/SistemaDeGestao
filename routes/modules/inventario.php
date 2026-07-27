<?php

use App\Modules\Inventario\Http\Controllers\InventarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('inventarios', [InventarioController::class, 'index'])->name('inventarios.index');
    Route::get('inventarios/novo', [InventarioController::class, 'create'])->name('inventarios.create');
    Route::post('inventarios', [InventarioController::class, 'store'])->name('inventarios.store');
    Route::get('inventarios/{inventario}', [InventarioController::class, 'show'])->name('inventarios.show');
    Route::put('inventarios/{inventario}/contagem', [InventarioController::class, 'salvarContagem'])->name('inventarios.contagem');
    Route::post('inventarios/{inventario}/finalizar', [InventarioController::class, 'finalizar'])->name('inventarios.finalizar');
    Route::post('inventarios/{inventario}/cancelar', [InventarioController::class, 'cancelar'])->name('inventarios.cancelar');
});
