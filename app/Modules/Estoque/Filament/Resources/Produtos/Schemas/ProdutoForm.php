<?php

namespace App\Modules\Estoque\Filament\Resources\Produtos\Schemas;

use App\Modules\Estoque\Enums\StatusProduto;
use App\Modules\Estoque\Enums\TipoVariacao;
use App\Modules\Estoque\Models\Produto;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProdutoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categoria_id')
                    ->label('Categoria')
                    ->relationship('categoria', 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('codigo_interno')
                    ->label('Código interno')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('codigo_barras')
                    ->label('Código de barras'),
                TextInput::make('unidade')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('UN, CX, PAR, KG...'),
                Textarea::make('descricao')
                    ->columnSpanFull(),
                Select::make('tipo_variacao')
                    ->label('Tipo de variação')
                    ->options(TipoVariacao::class)
                    ->default(TipoVariacao::Nenhum)
                    ->live()
                    ->required(),
                Select::make('status')
                    ->options(StatusProduto::class)
                    ->default(StatusProduto::Ativo)
                    ->required(),
                Repeater::make('variacoes')
                    ->label('Variações (numeração / tamanho)')
                    ->relationship()
                    ->orderColumn('ordem')
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('valor')
                            ->label('Valor')
                            ->required()
                            ->placeholder('Ex: 40 ou GG'),
                        Toggle::make('ativo')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->addActionLabel('Adicionar variação')
                    ->visible(fn (Get $get): bool => $get('tipo_variacao') !== TipoVariacao::Nenhum->value)
                    ->dehydrated(fn (Get $get): bool => $get('tipo_variacao') !== TipoVariacao::Nenhum->value)
                    ->columnSpanFull(),
                Section::make('Anexos')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('manual')
                            ->label('Manual (PDF)')
                            ->collection(Produto::COLECAO_MANUAL)
                            ->acceptedFileTypes(['application/pdf']),
                        SpatieMediaLibraryFileUpload::make('ficha_tecnica')
                            ->label('Ficha técnica (PDF)')
                            ->collection(Produto::COLECAO_FICHA_TECNICA)
                            ->acceptedFileTypes(['application/pdf']),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
