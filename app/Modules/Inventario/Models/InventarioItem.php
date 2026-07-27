<?php

namespace App\Modules\Inventario\Models;

use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ProdutoVariacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioItem extends Model
{
    use HasFactory;

    protected $table = 'inventario_itens';

    protected $fillable = [
        'inventario_id',
        'produto_id',
        'produto_variacao_id',
        'quantidade_sistema',
        'quantidade_contada',
    ];

    protected $casts = [
        'quantidade_sistema' => 'integer',
        'quantidade_contada' => 'integer',
    ];

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function produtoVariacao(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariacao::class);
    }

    public function contado(): bool
    {
        return ! is_null($this->quantidade_contada);
    }

    public function divergencia(): int
    {
        return $this->contado() ? $this->quantidade_contada - $this->quantidade_sistema : 0;
    }
}
