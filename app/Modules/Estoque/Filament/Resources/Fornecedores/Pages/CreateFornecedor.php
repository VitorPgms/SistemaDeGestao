<?php

namespace App\Modules\Estoque\Filament\Resources\Fornecedores\Pages;

use App\Modules\Estoque\Filament\Resources\Fornecedores\FornecedorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFornecedor extends CreateRecord
{
    protected static string $resource = FornecedorResource::class;
}
