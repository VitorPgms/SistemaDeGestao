<?php

namespace App\Modules\Usuarios\Filament\Resources\Users;

use App\Models\User;
use App\Modules\Core\Services\ActiveCdResolver;
use App\Modules\Usuarios\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Usuarios\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Usuarios\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Usuarios\Filament\Resources\Users\Schemas\UserForm;
use App\Modules\Usuarios\Filament\Resources\Users\Tables\UsersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * O model User não tem CdScope global (veja o comentário em App\Models\User)
     * para evitar recursão com Auth::user(), então o filtro por CD é aplicado
     * aqui, explicitamente, só na listagem deste Resource.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $cdId = app(ActiveCdResolver::class)->resolve();

        return $cdId === null ? $query : $query->where('cd_id', $cdId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
