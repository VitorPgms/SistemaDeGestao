<?php

namespace App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages;

use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\CentroDistribuicaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCentrosDistribuicao extends ListRecords
{
    protected static string $resource = CentroDistribuicaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
