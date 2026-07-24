<?php

namespace App\Modules\Estoque\Filament\Resources\Produtos\Pages;

use App\Modules\Estoque\Filament\Resources\Produtos\ProdutoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduto extends EditRecord
{
    protected static string $resource = ProdutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
