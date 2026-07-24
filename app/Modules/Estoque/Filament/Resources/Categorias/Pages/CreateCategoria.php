<?php

namespace App\Modules\Estoque\Filament\Resources\Categorias\Pages;

use App\Modules\Estoque\Filament\Resources\Categorias\CategoriaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoria extends CreateRecord
{
    protected static string $resource = CategoriaResource::class;
}
