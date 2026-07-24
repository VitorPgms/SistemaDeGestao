<?php

namespace App\Modules\Estoque\Filament\Resources\Categorias\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('descricao')
                    ->columnSpanFull(),
                Toggle::make('ativo')
                    ->default(true),
            ]);
    }
}
