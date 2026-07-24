<?php

namespace App\Modules\Organizacional\Filament\Resources\Setores\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class SetorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cd_id')
                    ->label('Centro de Distribuição')
                    ->relationship('centroDistribuicao', 'nome')
                    ->required()
                    ->visible(fn (): bool => Auth::user()->can('acessar-todos-cds'))
                    ->default(fn () => Auth::user()->cd_id),
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('cd_id', $get('cd_id') ?? Auth::user()->cd_id),
                    ),
                Toggle::make('ativo')
                    ->default(true),
            ]);
    }
}
