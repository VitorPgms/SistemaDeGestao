<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Scopes\CdScope;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Aplica isolamento multi-CD a um model: filtra automaticamente
 * as queries pelo CD ativo (CdScope) e preenche/valida o cd_id
 * a partir do usuário autenticado para usuários sem acesso global.
 */
trait BelongsToCd
{
    public static function bootBelongsToCd(): void
    {
        static::addGlobalScope(new CdScope);

        static::saving(function (Model $model) {
            $user = Auth::user();

            if (! $user || $user->can('acessar-todos-cds')) {
                return;
            }

            // Usuário comum nunca grava em CD diferente do seu, mesmo que
            // o valor tenha sido manipulado no client.
            $model->cd_id = $user->cd_id;
        });
    }

    public function centroDistribuicao(): BelongsTo
    {
        return $this->belongsTo(CentroDistribuicao::class, 'cd_id');
    }
}
