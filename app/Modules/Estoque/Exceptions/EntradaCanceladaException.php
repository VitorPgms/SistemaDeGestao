<?php

namespace App\Modules\Estoque\Exceptions;

use RuntimeException;

class EntradaCanceladaException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta entrada já está cancelada.');
    }
}
