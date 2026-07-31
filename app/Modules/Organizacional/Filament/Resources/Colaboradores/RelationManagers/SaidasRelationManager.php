<?php

namespace App\Modules\Organizacional\Filament\Resources\Colaboradores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Somente leitura: Saídas são criadas e editadas exclusivamente pela tela de
 * Operações (Entradas/Saídas), nunca a partir do cadastro do colaborador.
 */
class SaidasRelationManager extends RelationManager
{
    protected static string $relationship = 'saidas';

    protected static ?string $title = 'Saídas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('produto.nome')
                    ->label('Produto'),
                TextColumn::make('quantidade')
                    ->numeric(),
                TextColumn::make('data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('hora'),
                TextColumn::make('motivoSaida.nome')
                    ->label('Motivo'),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('data', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
