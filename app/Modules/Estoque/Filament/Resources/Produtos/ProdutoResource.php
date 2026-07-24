<?php

namespace App\Modules\Estoque\Filament\Resources\Produtos;

use App\Modules\Estoque\Filament\Resources\Produtos\Pages\CreateProduto;
use App\Modules\Estoque\Filament\Resources\Produtos\Pages\EditProduto;
use App\Modules\Estoque\Filament\Resources\Produtos\Pages\ListProdutos;
use App\Modules\Estoque\Filament\Resources\Produtos\Schemas\ProdutoForm;
use App\Modules\Estoque\Filament\Resources\Produtos\Tables\ProdutosTable;
use App\Modules\Estoque\Models\Produto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $slug = 'produtos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Produto';

    protected static ?string $pluralModelLabel = 'Produtos';

    public static function form(Schema $schema): Schema
    {
        return ProdutoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProdutosTable::configure($table);
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
            'index' => ListProdutos::route('/'),
            'create' => CreateProduto::route('/create'),
            'edit' => EditProduto::route('/{record}/edit'),
        ];
    }
}
