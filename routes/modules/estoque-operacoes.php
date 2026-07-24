<?php

use App\Modules\Estoque\Http\Controllers\EntradaController;
use App\Modules\Estoque\Http\Controllers\EstoqueController;
use App\Modules\Estoque\Http\Controllers\SaidaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('entradas', [EntradaController::class, 'index'])->name('entradas.index');
    Route::get('entradas/nova', [EntradaController::class, 'create'])->name('entradas.create');
    Route::post('entradas', [EntradaController::class, 'store'])->name('entradas.store');

    Route::get('saidas', [SaidaController::class, 'index'])->name('saidas.index');
    Route::get('saidas/nova', [SaidaController::class, 'create'])->name('saidas.create');
    Route::post('saidas', [SaidaController::class, 'store'])->name('saidas.store');

    Route::get('estoque', [EstoqueController::class, 'index'])->name('estoque.index');
    Route::get('estoque/{estoque}/editar', [EstoqueController::class, 'edit'])->name('estoque.edit');
    Route::put('estoque/{estoque}', [EstoqueController::class, 'update'])->name('estoque.update');
});
