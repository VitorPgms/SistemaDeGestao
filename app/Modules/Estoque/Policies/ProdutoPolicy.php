<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Estoque\Models\Produto;

class ProdutoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('produtos.view');
    }

    public function view(User $user, Produto $produto): bool
    {
        return $user->can('produtos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('produtos.manage');
    }

    public function update(User $user, Produto $produto): bool
    {
        return $user->can('produtos.manage');
    }

    public function delete(User $user, Produto $produto): bool
    {
        return $user->can('produtos.manage');
    }
}
