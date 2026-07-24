<?php

namespace App\Modules\Estoque\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Organizacional\Models\Colaborador;
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
        'colaborador_recebimento_id',
        'observacoes',
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

    public function colaboradorRecebimento(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_recebimento_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'registrado_por');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('estoque');
    }
}
