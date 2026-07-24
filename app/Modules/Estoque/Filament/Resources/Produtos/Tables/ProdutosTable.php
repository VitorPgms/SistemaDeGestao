<?php

namespace App\Modules\Estoque\Filament\Resources\Produtos\Tables;

use App\Modules\Estoque\Enums\StatusProduto;
use App\Modules\Estoque\Enums\TipoVariacao;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProdutosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('codigo_interno')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('categoria.nome')
                    ->label('Categoria')
                    ->searchable(),
                TextColumn::make('unidade'),
                TextColumn::make('tipo_variacao')
                    ->label('Variação')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('categoria_id')
                    ->label('Categoria')
                    ->relationship('categoria', 'nome'),
                SelectFilter::make('tipo_variacao')
                    ->options(TipoVariacao::class),
                SelectFilter::make('status')
                    ->options(StatusProduto::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
