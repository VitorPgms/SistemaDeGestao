<?php

namespace App\Modules\Organizacional\Enums;

enum StatusColaborador: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';

    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ativo => 'success',
            self::Inativo => 'danger',
        };
    }
}
