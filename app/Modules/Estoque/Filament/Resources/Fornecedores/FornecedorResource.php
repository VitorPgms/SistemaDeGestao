<?php

namespace App\Modules\Estoque\Filament\Resources\Fornecedores;

use App\Modules\Estoque\Filament\Resources\Fornecedores\Pages\CreateFornecedor;
use App\Modules\Estoque\Filament\Resources\Fornecedores\Pages\EditFornecedor;
use App\Modules\Estoque\Filament\Resources\Fornecedores\Pages\ListFornecedores;
use App\Modules\Estoque\Filament\Resources\Fornecedores\Schemas\FornecedorForm;
use App\Modules\Estoque\Filament\Resources\Fornecedores\Tables\FornecedoresTable;
use App\Modules\Estoque\Models\Fornecedor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FornecedorResource extends Resource
{
    protected static ?string $model = Fornecedor::class;

    protected static ?string $slug = 'fornecedores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Fornecedor';

    protected static ?string $pluralModelLabel = 'Fornecedores';

    protected static ?string $recordTitleAttribute = 'razao_social';

    public static function form(Schema $schema): Schema
    {
        return FornecedorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FornecedoresTable::configure($table);
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
            'index' => ListFornecedores::route('/'),
            'create' => CreateFornecedor::route('/create'),
            'edit' => EditFornecedor::route('/{record}/edit'),
        ];
    }
}
