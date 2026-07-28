<?php

namespace App\Modules\Bi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function marcarNotificacaoLida(Request $request, string $notificacao): RedirectResponse
    {
        $request->user()->unreadNotifications()->where('id', $notificacao)->first()?->markAsRead();

        return back();
    }
}
