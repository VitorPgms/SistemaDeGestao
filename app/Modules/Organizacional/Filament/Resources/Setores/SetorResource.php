<?php

namespace App\Modules\Organizacional\Filament\Resources\Setores;

use App\Modules\Organizacional\Filament\Resources\Setores\Pages\CreateSetor;
use App\Modules\Organizacional\Filament\Resources\Setores\Pages\EditSetor;
use App\Modules\Organizacional\Filament\Resources\Setores\Pages\ListSetores;
use App\Modules\Organizacional\Filament\Resources\Setores\Schemas\SetorForm;
use App\Modules\Organizacional\Filament\Resources\Setores\Tables\SetoresTable;
use App\Modules\Organizacional\Models\Setor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SetorResource extends Resource
{
    protected static ?string $model = Setor::class;

    protected static ?string $slug = 'setores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Setor';

    protected static ?string $pluralModelLabel = 'Setores';

    public static function form(Schema $schema): Schema
    {
        return SetorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SetoresTable::configure($table);
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
            'index' => ListSetores::route('/'),
            'create' => CreateSetor::route('/create'),
            'edit' => EditSetor::route('/{record}/edit'),
        ];
    }
}
