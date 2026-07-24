<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Estoque\Models\Fornecedor;

class FornecedorPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('fornecedores.view');
    }

    public function view(User $user, Fornecedor $fornecedor): bool
    {
        return $user->can('fornecedores.view') && $this->pertenceAoCdDoUsuario($user, $fornecedor);
    }

    public function create(User $user): bool
    {
        return $user->can('fornecedores.manage');
    }

    public function update(User $user, Fornecedor $fornecedor): bool
    {
        return $user->can('fornecedores.manage') && $this->pertenceAoCdDoUsuario($user, $fornecedor);
    }

    public function delete(User $user, Fornecedor $fornecedor): bool
    {
        return $user->can('fornecedores.manage') && $this->pertenceAoCdDoUsuario($user, $fornecedor);
    }
}
