<?php

namespace App\Modules\Organizacional\Filament\Resources\Colaboradores\Tables;

use App\Modules\Organizacional\Enums\StatusColaborador;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ColaboradoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('funcao')
                    ->label('Função')
                    ->searchable(),
                TextColumn::make('setor.nome')
                    ->label('Setor')
                    ->searchable(),
                TextColumn::make('centroDistribuicao.nome')
                    ->label('Centro de Distribuição')
                    ->visible(fn (): bool => Auth::user()->can('acessar-todos-cds'))
                    ->searchable(),
                TextColumn::make('data_admissao')
                    ->label('Admissão')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_demissao')
                    ->label('Demissão')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusColaborador $state) => $state->label())
                    ->color(fn (StatusColaborador $state) => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => collect(StatusColaborador::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
                SelectFilter::make('setor_id')
                    ->label('Setor')
                    ->relationship('setor', 'nome'),
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
