<?php

namespace App\Modules\Inventario\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusInventario: string implements HasColor, HasLabel
{
    case EmAndamento = 'em_andamento';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';

    public function getLabel(): string
    {
        return match ($this) {
            self::EmAndamento => 'Em andamento',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EmAndamento => 'warning',
            self::Concluido => 'success',
            self::Cancelado => 'gray',
        };
    }
}
