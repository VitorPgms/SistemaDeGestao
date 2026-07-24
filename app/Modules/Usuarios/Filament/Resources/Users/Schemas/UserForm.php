<?php

namespace App\Modules\Usuarios\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('cd_id')
                    ->label('Centro de Distribuição')
                    ->relationship('centroDistribuicao', 'nome')
                    ->required()
                    ->visible(fn (): bool => Auth::user()->can('acessar-todos-cds'))
                    ->default(fn () => Auth::user()->cd_id),
                Select::make('roles')
                    ->label('Papel de acesso')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => ucfirst($record->name))
                    ->multiple()
                    ->minItems(1)
                    ->maxItems(1)
                    ->preload()
                    ->required(),
                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                Toggle::make('ativo')
                    ->default(true),
            ]);
    }
}
