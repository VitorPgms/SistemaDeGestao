<?php

namespace App\Modules\Estoque\Policies;

use App\Models\User;
use App\Modules\Core\Concerns\AuthorizesCdOwnership;
use App\Modules\Estoque\Models\ResponsavelRecebimento;

class ResponsavelRecebimentoPolicy
{
    use AuthorizesCdOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('responsaveis-recebimento.view');
    }

    public function view(User $user, ResponsavelRecebimento $responsavelRecebimento): bool
    {
        return $user->can('responsaveis-recebimento.view') && $this->pertenceAoCdDoUsuario($user, $responsavelRecebimento);
    }

    public function create(User $user): bool
    {
        return $user->can('responsaveis-recebimento.manage');
    }

    public function update(User $user, ResponsavelRecebimento $responsavelRecebimento): bool
    {
        return $user->can('responsaveis-recebimento.manage') && $this->pertenceAoCdDoUsuario($user, $responsavelRecebimento);
    }

    public function delete(User $user, ResponsavelRecebimento $responsavelRecebimento): bool
    {
        return $user->can('responsaveis-recebimento.manage') && $this->pertenceAoCdDoUsuario($user, $responsavelRecebimento);
    }
}
