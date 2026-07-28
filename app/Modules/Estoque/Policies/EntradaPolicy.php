<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Estoque\Models\Entrada;

class EntradaPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('entradas.view');
    }

    public function view(User $user, Entrada $entrada): bool
    {
        return $user->can('entradas.view') && $this->pertenceAoCdDoUsuario($user, $entrada);
    }

    public function create(User $user): bool
    {
        return $user->can('entradas.manage');
    }

    public function update(User $user, Entrada $entrada): bool
    {
        return $user->can('entradas.manage') && $this->pertenceAoCdDoUsuario($user, $entrada);
    }
}
