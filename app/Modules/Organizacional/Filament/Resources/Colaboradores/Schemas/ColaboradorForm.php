<?php

namespace App\Modules\Organizacional\Filament\Resources\Colaboradores\Schemas;

use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\Setor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;

class ColaboradorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cd_id')
                    ->label('Centro de Distribuição')
                    ->relationship('centroDistribuicao', 'nome')
                    ->required()
                    ->live()
                    ->visible(fn (): bool => Auth::user()->can('acessar-todos-cds'))
                    ->default(fn () => Auth::user()->cd_id)
                    ->afterStateUpdated(fn (callable $set) => $set('setor_id', null)),
                Select::make('setor_id')
                    ->label('Setor')
                    ->required()
                    ->options(
                        fn (Get $get) => Setor::query()
                            ->where('cd_id', $get('cd_id') ?? Auth::user()->cd_id)
                            ->where('ativo', true)
                            ->pluck('nome', 'id'),
                    )
                    ->disabled(fn (Get $get): bool => blank($get('cd_id') ?? Auth::user()->cd_id))
                    ->exists(
                        table: 'setores',
                        column: 'id',
                        modifyRuleUsing: fn (Exists $rule, Get $get) => $rule->where('cd_id', $get('cd_id') ?? Auth::user()->cd_id),
                    )
                    ->searchable(),
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('data_nascimento')
                    ->label('Data de nascimento')
                    ->native(false)
                    ->maxDate(now()),
                TextInput::make('funcao')
                    ->label('Função')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tamanho_vestimenta')
                    ->label('Tamanho de vestimenta')
                    ->maxLength(255),
                DatePicker::make('data_admissao')
                    ->label('Data de admissão')
                    ->native(false)
                    ->required()
                    ->maxDate(now()),
                DatePicker::make('data_demissao')
                    ->label('Data de demissão')
                    ->native(false)
                    ->afterOrEqual('data_admissao')
                    ->requiredIf('status', StatusColaborador::Inativo->value)
                    ->prohibitedUnless('status', StatusColaborador::Inativo->value),
                DatePicker::make('data_ultimo_exame_periodico')
                    ->label('Data do último exame periódico')
                    ->native(false)
                    ->maxDate(now()),
                DatePicker::make('data_proximo_exame_periodico')
                    ->label('Data do próximo exame periódico')
                    ->native(false),
                Select::make('status')
                    ->options(StatusColaborador::class)
                    ->default(StatusColaborador::Ativo)
                    ->required()
                    ->live(),
                Section::make('Documentos')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('documentos')
                            ->label('Anexos')
                            ->collection(Colaborador::COLECAO_DOCUMENTOS)
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->downloadable()
                            ->openable(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
