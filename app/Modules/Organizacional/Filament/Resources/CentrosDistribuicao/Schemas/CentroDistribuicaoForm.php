<?php

namespace App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CentroDistribuicaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('codigo')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state)),
                TextInput::make('endereco')
                    ->maxLength(255),
                TextInput::make('cidade')
                    ->maxLength(255),
                TextInput::make('estado')
                    ->label('UF')
                    ->maxLength(2),
                Toggle::make('ativo')
                    ->default(true),
            ]);
    }
}
