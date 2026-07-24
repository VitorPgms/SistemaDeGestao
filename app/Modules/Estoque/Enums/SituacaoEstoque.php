<?php

namespace App\Modules\Estoque\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SituacaoEstoque: string implements HasColor, HasLabel
{
    case Normal = 'normal';
    case Atencao = 'atencao';
    case Critico = 'critico';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Atencao => 'Atenção',
            self::Critico => 'Crítico',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Normal => 'success',
            self::Atencao => 'warning',
            self::Critico => 'danger',
        };
    }
}
