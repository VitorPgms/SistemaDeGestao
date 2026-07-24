<?php

namespace App\Modules\Organizacional\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Organizacional\Models\Setor;

class SetorPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('setores.view');
    }

    public function view(User $user, Setor $setor): bool
    {
        return $user->can('setores.view') && $this->pertenceAoCdDoUsuario($user, $setor);
    }

    public function create(User $user): bool
    {
        return $user->can('setores.manage');
    }

    public function update(User $user, Setor $setor): bool
    {
        return $user->can('setores.manage') && $this->pertenceAoCdDoUsuario($user, $setor);
    }

    public function delete(User $user, Setor $setor): bool
    {
        return $user->can('setores.manage') && $this->pertenceAoCdDoUsuario($user, $setor);
    }
}
