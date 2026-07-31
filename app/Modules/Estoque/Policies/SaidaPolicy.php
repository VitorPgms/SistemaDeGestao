<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Estoque\Models\Saida;

class SaidaPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('saidas.view');
    }

    public function view(User $user, Saida $saida): bool
    {
        return $user->can('saidas.view') && $this->pertenceAoCdDoUsuario($user, $saida);
    }

    public function create(User $user): bool
    {
        return $user->can('saidas.manage');
    }

    public function update(User $user, Saida $saida): bool
    {
        return $user->can('saidas.manage') && $this->pertenceAoCdDoUsuario($user, $saida);
    }
}
