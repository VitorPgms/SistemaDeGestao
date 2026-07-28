<?php

namespace App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ResponsaveisRecebimentoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('centroDistribuicao.nome')
                    ->label('Centro de Distribuição')
                    ->visible(fn (): bool => Auth::user()->can('acessar-todos-cds'))
                    ->searchable(),
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
