<?php

namespace App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao;

use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages\CreateCentroDistribuicao;
use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages\EditCentroDistribuicao;
use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages\ListCentrosDistribuicao;
use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Schemas\CentroDistribuicaoForm;
use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Tables\CentrosDistribuicaoTable;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CentroDistribuicaoResource extends Resource
{
    protected static ?string $model = CentroDistribuicao::class;

    protected static ?string $slug = 'centros-distribuicao';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Centro de Distribuição';

    protected static ?string $pluralModelLabel = 'Centros de Distribuição';

    public static function form(Schema $schema): Schema
    {
        return CentroDistribuicaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentrosDistribuicaoTable::configure($table);
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
            'index' => ListCentrosDistribuicao::route('/'),
            'create' => CreateCentroDistribuicao::route('/create'),
            'edit' => EditCentroDistribuicao::route('/{record}/edit'),
        ];
    }
}
