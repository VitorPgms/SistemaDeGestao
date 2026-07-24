<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Estoque\Models\Estoque;

class EstoquePolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('estoque.view');
    }

    public function view(User $user, Estoque $estoque): bool
    {
        return $user->can('estoque.view') && $this->pertenceAoCdDoUsuario($user, $estoque);
    }

    public function update(User $user, Estoque $estoque): bool
    {
        return $user->can('estoque.manage') && $this->pertenceAoCdDoUsuario($user, $estoque);
    }
}
