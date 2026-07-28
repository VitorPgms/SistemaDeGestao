<?php

namespace App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages;

use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\ResponsavelRecebimentoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResponsavelRecebimento extends EditRecord
{
    protected static string $resource = ResponsavelRecebimentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
