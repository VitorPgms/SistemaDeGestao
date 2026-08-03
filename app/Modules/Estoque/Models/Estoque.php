<?php

namespace App\Modules\Estoque\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Estoque\Enums\SituacaoEstoque;
use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Enums\StatusSaida;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estoque extends Model
{
    use BelongsToCd;
    use HasFactory;

    protected $table = 'estoques';

    protected $fillable = [
        'cd_id',
        'produto_id',
        'produto_variacao_id',
        'quantidade_atual',
        'quantidade_minima',
        'quantidade_ideal',
        'localizacao',
        'fornecedor_preferencial_id',
        'ultima_entrada_at',
        'ultima_saida_at',
    ];

    protected $casts = [
        'quantidade_atual' => 'integer',
        'quantidade_minima' => 'integer',
        'quantidade_ideal' => 'integer',
        'ultima_entrada_at' => 'datetime',
        'ultima_saida_at' => 'datetime',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function produtoVariacao(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariacao::class);
    }

    public function fornecedorPreferencial(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_preferencial_id');
    }

    /**
     * Crítico: no ou abaixo do mínimo. Atenção: entre o mínimo e o ideal.
     * Normal: no ideal ou acima. Se mínimo/ideal nunca foram configurados
     * (ambos zerados), não há alarme para disparar — considera Normal.
     */
    public function situacao(): SituacaoEstoque
    {
        if ($this->quantidade_minima === 0 && $this->quantidade_ideal === 0) {
            return SituacaoEstoque::Normal;
        }

        if ($this->quantidade_atual <= $this->quantidade_minima) {
            return SituacaoEstoque::Critico;
        }

        if ($this->quantidade_atual < $this->quantidade_ideal) {
            return SituacaoEstoque::Atencao;
        }

        return SituacaoEstoque::Normal;
    }

    /**
     * Mesma regra usada em situacao(), como scope de query para os
     * indicadores do Dashboard (contagem sem carregar todos os registros).
     */
    public function scopeCritico(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('quantidade_minima', '>', 0)->orWhere('quantidade_ideal', '>', 0);
        })->whereColumn('quantidade_atual', '<=', 'quantidade_minima');
    }

    public function scopeAtencao(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('quantidade_minima', '>', 0)->orWhere('quantidade_ideal', '>', 0);
        })->whereColumn('quantidade_atual', '>', 'quantidade_minima')
            ->whereColumn('quantidade_atual', '<', 'quantidade_ideal');
    }

    /**
     * Registro operacionalmente relevante para a listagem padrão: tem
     * estoque, tem mínimo/ideal configurado (ainda que zerado o atual, para
     * não esconder situação Crítica) ou tem entrada/saída ATIVA vinculada.
     *
     * Não basta checar ultima_entrada_at/ultima_saida_at: esses timestamps
     * não são revertidos ao cancelar uma Entrada/Saída (cancelarEntrada e
     * cancelarSaida em EstoqueService só devolvem a quantidade), então uma
     * entrada cancelada deixaria o registro "com movimentação" para sempre,
     * mesmo sem nenhuma relevância operacional real. Por isso a checagem
     * aqui vai direto nas tabelas de origem, filtrando status Ativa.
     */
    public function scopeRelevanteParaListagem(Builder $query): Builder
    {
        // produto_variacao_id é nullable, e comparar NULL = NULL não bate em
        // SQL padrão — por isso o whereColumn vem acompanhado do caso "os
        // dois são nulos", em vez de um operador de igualdade nula específico
        // de banco (que quebraria a suíte de testes, rodada em SQLite).
        $mesmaVariacao = function ($sub) {
            $sub->whereColumn('produto_variacao_id', 'estoques.produto_variacao_id')
                ->orWhere(function ($q) {
                    $q->whereNull('produto_variacao_id')->whereNull('estoques.produto_variacao_id');
                });
        };

        return $query->where(function (Builder $q) use ($mesmaVariacao) {
            $q->where('quantidade_atual', '>', 0)
                ->orWhere('quantidade_minima', '>', 0)
                ->orWhere('quantidade_ideal', '>', 0)
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                    ->from('entradas')
                    ->whereColumn('entradas.cd_id', 'estoques.cd_id')
                    ->whereColumn('entradas.produto_id', 'estoques.produto_id')
                    ->where($mesmaVariacao)
                    ->where('entradas.status', StatusEntrada::Ativa->value))
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                    ->from('saidas')
                    ->whereColumn('saidas.cd_id', 'estoques.cd_id')
                    ->whereColumn('saidas.produto_id', 'estoques.produto_id')
                    ->where($mesmaVariacao)
                    ->where('saidas.status', StatusSaida::Ativa->value));
        });
    }
}
