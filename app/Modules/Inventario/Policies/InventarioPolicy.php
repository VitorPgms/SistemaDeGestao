<?php

namespace App\Modules\Inventario\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Inventario\Models\Inventario;

class InventarioPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('inventarios.view');
    }

    public function view(User $user, Inventario $inventario): bool
    {
        return $user->can('inventarios.view') && $this->pertenceAoCdDoUsuario($user, $inventario);
    }

    public function create(User $user): bool
    {
        return $user->can('inventarios.manage');
    }

    public function update(User $user, Inventario $inventario): bool
    {
        return $user->can('inventarios.manage') && $this->pertenceAoCdDoUsuario($user, $inventario);
    }
}
