<?php

namespace App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ResponsavelRecebimentoForm
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
                    ->maxLength(255),
                Toggle::make('ativo')
                    ->default(true),
            ]);
    }
}
