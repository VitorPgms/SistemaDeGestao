<?php

namespace App\Modules\Estoque\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusProduto: string implements HasColor, HasLabel
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ativo => 'success',
            self::Inativo => 'danger',
        };
    }
}
