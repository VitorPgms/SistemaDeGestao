<?php

use App\Modules\Bi\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// GET /admin/dashboard é registrada automaticamente pela Filament Page
// App\Modules\Bi\Filament\Pages\Dashboard — só a ação de escrita continua
// como rota Blade comum.
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('dashboard/notificacoes/{notificacao}/marcar-lida', [DashboardController::class, 'marcarNotificacaoLida'])->name('dashboard.notificacoes.marcar-lida');
});
