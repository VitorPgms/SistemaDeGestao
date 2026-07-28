<?php

namespace App\Modules\Estoque\Filament\Resources\Fornecedores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FornecedoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('razao_social')
            ->columns([
                TextColumn::make('razao_social')
                    ->label('Razão social')
                    ->searchable(),
                TextColumn::make('nome_fantasia')
                    ->label('Nome fantasia')
                    ->searchable(),
                TextColumn::make('centroDistribuicao.nome')
                    ->label('Centro de Distribuição')
                    ->visible(fn (): bool => Auth::user()->can('acessar-todos-cds'))
                    ->searchable(),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),
                TextColumn::make('telefone')
                    ->searchable(),
                TextColumn::make('nome_responsavel')
                    ->label('Responsável')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('ativo')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('ativo'),
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
