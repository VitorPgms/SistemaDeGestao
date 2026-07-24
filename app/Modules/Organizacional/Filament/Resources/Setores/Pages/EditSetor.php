<?php

namespace App\Modules\Organizacional\Filament\Resources\Setores\Pages;

use App\Modules\Organizacional\Filament\Resources\Setores\SetorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSetor extends EditRecord
{
    protected static string $resource = SetorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
