<?php

namespace App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages;

use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\CentroDistribuicaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCentroDistribuicao extends CreateRecord
{
    protected static string $resource = CentroDistribuicaoResource::class;
}
