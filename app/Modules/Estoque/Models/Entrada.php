<?php

namespace App\Modules\Estoque\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Estoque\Enums\StatusEntrada;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Entrada extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

    protected $table = 'entradas';

    protected $fillable = [
        'cd_id',
        'produto_id',
        'produto_variacao_id',
        'fornecedor_id',
        'numero_nota_fiscal',
        'data_compra',
        'data_entrega',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'status',
        'responsavel_recebimento_id',
        'observacoes',
        'motivo_cancelamento',
        'cancelado_por',
        'cancelado_em',
        'registrado_por',
        'origem_type',
        'origem_id',
    ];

    protected $casts = [
        'data_compra' => 'date',
        'data_entrega' => 'date',
        'quantidade' => 'integer',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'status' => StatusEntrada::class,
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

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function responsavelRecebimento(): BelongsTo
    {
        return $this->belongsTo(ResponsavelRecebimento::class);
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
