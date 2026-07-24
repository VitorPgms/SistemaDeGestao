<?php

namespace App\Modules\Core\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Defesa em profundidade para Policies: mesmo que o CdScope já filtre
 * a listagem, view/update/delete de um registro específico (ex: via
 * URL manipulada) precisam confirmar que o registro pertence ao CD
 * do usuário, a menos que ele tenha acesso global.
 */
trait AuthorizesCdOwnership
{
    protected function pertenceAoCdDoUsuario(User $user, Model $model): bool
    {
        return $user->can('acessar-todos-cds') || $model->cd_id === $user->cd_id;
    }
}
