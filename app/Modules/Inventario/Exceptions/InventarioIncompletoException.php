<?php

namespace App\Modules\Inventario\Exceptions;

use RuntimeException;

class InventarioIncompletoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ainda existem itens sem contagem registrada. Conte todos os itens antes de finalizar.');
    }
}
