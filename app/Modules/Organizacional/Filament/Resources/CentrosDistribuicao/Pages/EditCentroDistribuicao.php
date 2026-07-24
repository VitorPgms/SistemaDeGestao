<?php

namespace App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages;

use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\CentroDistribuicaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCentroDistribuicao extends EditRecord
{
    protected static string $resource = CentroDistribuicaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
