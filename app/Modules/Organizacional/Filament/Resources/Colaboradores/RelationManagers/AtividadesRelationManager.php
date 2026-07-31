<?php

namespace App\Modules\Organizacional\Filament\Resources\Colaboradores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/**
 * Somente leitura: mostra as alterações cadastrais do colaborador já
 * registradas automaticamente pelo Activity Log (Colaborador::getActivitylogOptions()).
 * Não é possível criar/editar/excluir entradas de auditoria por aqui.
 */
class AtividadesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Alterações Cadastrais';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Criado',
                        'updated' => 'Atualizado',
                        'deleted' => 'Excluído',
                        default => $state ?? '—',
                    }),
                TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->default('—'),
                TextColumn::make('alteracoes')
                    ->label('Alterações')
                    ->state(function (Activity $record): string {
                        $depois = $record->properties->get('attributes', []);
                        $antes = $record->properties->get('old', []);

                        if (empty($depois)) {
                            return '—';
                        }

                        return collect($depois)
                            ->map(fn ($valor, $campo) => "{$campo}: ".(data_get($antes, $campo) ?? '—').' → '.($valor ?? '—'))
                            ->implode('; ');
                    })
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
