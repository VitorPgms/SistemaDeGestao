<?php

namespace App\Modules\Estoque\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoSaida extends Model
{
    use HasFactory;

    protected $table = 'motivos_saida';

    protected $fillable = [
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Motivo usado nos ajustes automáticos gerados pelo Inventário
     * (App\Modules\Estoque\Services\EstoqueService::registrarAjusteInventario).
     * Auto-criado se alguém apagar o registro semeado — o ajuste de estoque
     * não pode falhar por causa de um cadastro de apoio ausente.
     */
    public static function ajusteInventario(): self
    {
        return self::query()->firstOrCreate(['nome' => 'Ajuste de Inventário']);
    }
}
