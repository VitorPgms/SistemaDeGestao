<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Estoque\Models\Categoria;

class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categorias.view');
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $user->can('categorias.view');
    }

    public function create(User $user): bool
    {
        return $user->can('categorias.manage');
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->can('categorias.manage');
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->can('categorias.manage');
    }
}
