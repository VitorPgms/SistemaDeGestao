<?php

namespace App\Modules\Core\Scopes;

use App\Modules\Core\Services\ActiveCdResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CdScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $cdId = app(ActiveCdResolver::class)->resolve();

        if ($cdId !== null) {
            $builder->where($model->qualifyColumn('cd_id'), $cdId);
        }
    }
}
