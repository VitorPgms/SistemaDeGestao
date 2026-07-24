<?php

namespace App\Modules\Estoque\Filament\Resources\Produtos\Pages;

use App\Modules\Estoque\Filament\Resources\Produtos\ProdutoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduto extends CreateRecord
{
    protected static string $resource = ProdutoResource::class;
}
