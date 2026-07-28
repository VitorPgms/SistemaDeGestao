<?php

namespace App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento;

use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages\CreateResponsavelRecebimento;
use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages\EditResponsavelRecebimento;
use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages\ListResponsaveisRecebimento;
use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Schemas\ResponsavelRecebimentoForm;
use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Tables\ResponsaveisRecebimentoTable;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResponsavelRecebimentoResource extends Resource
{
    protected static ?string $model = ResponsavelRecebimento::class;

    protected static ?string $slug = 'responsaveis-recebimento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Responsável pelo Recebimento';

    protected static ?string $pluralModelLabel = 'Responsáveis pelo Recebimento';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return ResponsavelRecebimentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResponsaveisRecebimentoTable::configure($table);
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
            'index' => ListResponsaveisRecebimento::route('/'),
            'create' => CreateResponsavelRecebimento::route('/create'),
            'edit' => EditResponsavelRecebimento::route('/{record}/edit'),
        ];
    }
}
