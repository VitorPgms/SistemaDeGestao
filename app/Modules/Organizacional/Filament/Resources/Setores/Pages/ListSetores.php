<?php

namespace App\Modules\Organizacional\Filament\Resources\Setores\Pages;

use App\Modules\Organizacional\Filament\Resources\Setores\SetorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSetores extends ListRecords
{
    protected static string $resource = SetorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
