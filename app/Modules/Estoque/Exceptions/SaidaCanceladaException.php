<?php

namespace App\Modules\Estoque\Exceptions;

use RuntimeException;

class SaidaCanceladaException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta saída já está cancelada.');
    }
}
