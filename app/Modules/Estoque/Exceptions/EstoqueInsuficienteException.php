<?php

namespace App\Modules\Estoque\Exceptions;

use RuntimeException;

class EstoqueInsuficienteException extends RuntimeException
{
    public function __construct(int $quantidadeDisponivel, int $quantidadeSolicitada)
    {
        parent::__construct(
            "Estoque insuficiente: disponível {$quantidadeDisponivel}, solicitado {$quantidadeSolicitada}.",
        );
    }
}
