<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// As telas de Operações/BI ficam sob o mesmo prefixo /admin dos Cadastros
// (Filament) só para a URL ficar consistente — continuam sendo rotas Blade
// normais, fora do painel Filament.
Route::prefix('admin')->group(function () {
    require __DIR__.'/modules/estoque-operacoes.php';
    require __DIR__.'/modules/inventario.php';
    require __DIR__.'/modules/bi.php';
});
