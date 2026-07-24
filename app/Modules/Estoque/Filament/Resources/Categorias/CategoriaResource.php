<?php

namespace App\Modules\Estoque\Filament\Resources\Categorias;

use App\Modules\Estoque\Filament\Resources\Categorias\Pages\CreateCategoria;
use App\Modules\Estoque\Filament\Resources\Categorias\Pages\EditCategoria;
use App\Modules\Estoque\Filament\Resources\Categorias\Pages\ListCategorias;
use App\Modules\Estoque\Filament\Resources\Categorias\Schemas\CategoriaForm;
use App\Modules\Estoque\Filament\Resources\Categorias\Tables\CategoriasTable;
use App\Modules\Estoque\Models\Categoria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static ?string $slug = 'categorias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Categoria';

    protected static ?string $pluralModelLabel = 'Categorias';

    public static function form(Schema $schema): Schema
    {
        return CategoriaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategorias::route('/'),
            'create' => CreateCategoria::route('/create'),
            'edit' => EditCategoria::route('/{record}/edit'),
        ];
    }
}
