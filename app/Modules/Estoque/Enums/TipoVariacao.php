<?php

namespace App\Modules\Estoque\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoVariacao: string implements HasLabel
{
    case Nenhum = 'nenhum';
    case Numeracao = 'numeracao';
    case Tamanho = 'tamanho';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nenhum => 'Nenhuma',
            self::Numeracao => 'Numeração',
            self::Tamanho => 'Tamanho',
        };
    }
}
