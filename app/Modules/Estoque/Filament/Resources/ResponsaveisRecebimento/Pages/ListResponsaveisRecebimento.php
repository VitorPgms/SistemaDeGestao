<?php

namespace App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages;

use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\ResponsavelRecebimentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResponsaveisRecebimento extends ListRecords
{
    protected static string $resource = ResponsavelRecebimentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
