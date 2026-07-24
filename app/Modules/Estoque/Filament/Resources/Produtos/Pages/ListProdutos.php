<?php

namespace App\Modules\Estoque\Filament\Resources\Produtos\Pages;

use App\Modules\Estoque\Filament\Resources\Produtos\ProdutoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProdutos extends ListRecords
{
    protected static string $resource = ProdutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
