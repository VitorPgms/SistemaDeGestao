<?php

namespace App\Modules\Organizacional\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Organizacional\Models\Colaborador;

class ColaboradorPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('colaboradores.view');
    }

    public function view(User $user, Colaborador $colaborador): bool
    {
        return $user->can('colaboradores.view') && $this->pertenceAoCdDoUsuario($user, $colaborador);
    }

    public function create(User $user): bool
    {
        return $user->can('colaboradores.manage');
    }

    public function update(User $user, Colaborador $colaborador): bool
    {
        return $user->can('colaboradores.manage') && $this->pertenceAoCdDoUsuario($user, $colaborador);
    }

    public function delete(User $user, Colaborador $colaborador): bool
    {
        return $user->can('colaboradores.manage') && $this->pertenceAoCdDoUsuario($user, $colaborador);
    }
}
