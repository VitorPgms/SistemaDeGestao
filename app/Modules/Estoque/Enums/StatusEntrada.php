<?php

namespace App\Modules\Estoque\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusEntrada: string implements HasColor, HasLabel
{
    case Ativa = 'ativa';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ativa => 'Ativa',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ativa => 'success',
            self::Cancelada => 'gray',
        };
    }
}
