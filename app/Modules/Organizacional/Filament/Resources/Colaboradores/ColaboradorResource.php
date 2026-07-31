<?php

namespace App\Modules\Organizacional\Filament\Resources\Colaboradores;

use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\CreateColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\EditColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\ListColaboradores;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\RelationManagers\AtividadesRelationManager;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\RelationManagers\SaidasRelationManager;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Schemas\ColaboradorForm;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Tables\ColaboradoresTable;
use App\Modules\Organizacional\Models\Colaborador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ColaboradorResource extends Resource
{
    protected static ?string $model = Colaborador::class;

    protected static ?string $slug = 'colaboradores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Colaborador';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return ColaboradorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ColaboradoresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SaidasRelationManager::class,
            AtividadesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListColaboradores::route('/'),
            'create' => CreateColaborador::route('/create'),
            'edit' => EditColaborador::route('/{record}/edit'),
        ];
    }
}
