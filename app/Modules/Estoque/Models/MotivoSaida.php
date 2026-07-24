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
}
