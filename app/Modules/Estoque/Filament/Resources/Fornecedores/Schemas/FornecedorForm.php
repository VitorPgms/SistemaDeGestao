<?php

namespace App\Modules\Estoque\Filament\Resources\Fornecedores\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class FornecedorForm
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
                TextInput::make('razao_social')
                    ->label('Razão social')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nome_fantasia')
                    ->label('Nome fantasia')
                    ->maxLength(255),
                TextInput::make('nome_responsavel')
                    ->label('Nome do responsável')
                    ->maxLength(255),
                TextInput::make('cnpj')
                    ->label('CNPJ')
                    ->mask('99.999.999/9999-99')
                    ->maxLength(18)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('cd_id', $get('cd_id') ?? Auth::user()->cd_id),
                    ),
                TextInput::make('telefone')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Textarea::make('observacoes')
                    ->label('Observações')
                    ->columnSpanFull(),
                Toggle::make('ativo')
                    ->default(true),
            ]);
    }
}
