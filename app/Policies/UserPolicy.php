<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;

class UserPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('usuarios.view') && $this->pertenceAoCdDoUsuario($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.manage') && $this->pertenceAoCdDoUsuario($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->can('usuarios.manage') && $this->pertenceAoCdDoUsuario($user, $model);
    }
}
