<?php

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Resolve qual cd_id deve filtrar as queries do usuário autenticado.
 *
 * Retorna null quando nenhuma filtragem por CD deve ser aplicada
 * (administrador visualizando "Todos os CDs").
 */
class ActiveCdResolver
{
    public const SESSION_KEY = 'active_cd_filter';

    public function resolve(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (! $user->can('acessar-todos-cds')) {
            return $user->cd_id;
        }

        return Session::get(self::SESSION_KEY);
    }

    public function setFilter(?int $cdId): void
    {
        Session::put(self::SESSION_KEY, $cdId);
    }
}
