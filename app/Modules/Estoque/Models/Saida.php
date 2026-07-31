<?php

namespace App\Modules\Estoque\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Saida extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

    protected $table = 'saidas';

    protected $fillable = [
        'cd_id',
        'produto_id',
        'produto_variacao_id',
        'quantidade',
        'colaborador_id',
        'liberado_por',
        'motivo_saida_id',
        'status_colaborador_snapshot',
        'data',
        'hora',
        'observacoes',
        'status',
        'motivo_cancelamento',
        'cancelado_por',
        'cancelado_em',
        'registrado_por',
        'origem_type',
        'origem_id',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'status_colaborador_snapshot' => StatusColaborador::class,
        'data' => 'date',
        'status' => StatusSaida::class,
        'cancelado_em' => 'datetime',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function produtoVariacao(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariacao::class);
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function liberadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'liberado_por');
    }

    public function motivoSaida(): BelongsTo
    {
        return $this->belongsTo(MotivoSaida::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'registrado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelado_por');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('estoque');
    }
}
