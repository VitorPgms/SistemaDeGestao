<?php

namespace App\Modules\Organizacional\Policies;

use App\Models\User;
use App\Modules\Organizacional\Models\CentroDistribuicao;

class CentroDistribuicaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('centros-distribuicao.view');
    }

    public function view(User $user, CentroDistribuicao $centroDistribuicao): bool
    {
        return $user->can('centros-distribuicao.view');
    }

    public function create(User $user): bool
    {
        return $user->can('centros-distribuicao.manage');
    }

    public function update(User $user, CentroDistribuicao $centroDistribuicao): bool
    {
        return $user->can('centros-distribuicao.manage');
    }

    public function delete(User $user, CentroDistribuicao $centroDistribuicao): bool
    {
        return $user->can('centros-distribuicao.manage');
    }
}
