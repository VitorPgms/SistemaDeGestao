<?php

namespace App\Modules\Estoque\Services;

use App\Models\User;
use App\Modules\Estoque\Enums\SituacaoEstoque;
use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Estoque\Exceptions\EntradaCanceladaException;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
use App\Modules\Estoque\Exceptions\SaidaCanceladaException;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Estoque\Notifications\EstoqueMinimoAtingido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Único ponto do sistema que altera `estoques.quantidade_atual`.
 *
 * Entradas, saídas e (futuramente) ajustes de inventário são todos motivos
 * diferentes de chamar este serviço — nenhum outro lugar do código deve
 * escrever nessa coluna diretamente, para a quantidade em estoque nunca
 * ficar dessincronizada do histórico de movimentações.
 */
class EstoqueService
{
    public function registrarEntrada(array $dados): Entrada
    {
        return DB::transaction(function () use ($dados) {
            $entrada = Entrada::create($dados);

            $estoque = $this->localizarOuCriarEstoque(
                $entrada->cd_id,
                $entrada->produto_id,
                $entrada->produto_variacao_id,
            );

            $estoque->quantidade_atual += $entrada->quantidade;
            $estoque->ultima_entrada_at = now();
            $estoque->save();

            return $entrada;
        });
    }

    /**
     * Recalcula o estoque para refletir a entrada editada.
     *
     * Quando produto/variação não mudam (caso comum), aplica só a diferença
     * entre a quantidade nova e a antiga, bloqueando apenas se essa diferença
     * não couber no saldo atual. Isso evita falso bloqueio quando o estoque
     * agregado do produto já caiu abaixo da quantidade antiga desta Entrada
     * por causa de Saídas — o que não impede reverter/reaplicar o valor
     * total, mas não impede aplicar só a diferença, se ela couber.
     *
     * Quando produto/variação mudam, mantém o comportamento anterior: reverte
     * a quantidade antiga do produto/variação originais e aplica a nova
     * quantidade ao produto/variação novos.
     *
     * @throws EntradaCanceladaException
     * @throws EstoqueInsuficienteException
     */
    public function atualizarEntrada(Entrada $entrada, array $dados): Entrada
    {
        if ($entrada->status === StatusEntrada::Cancelada) {
            throw new EntradaCanceladaException;
        }

        return DB::transaction(function () use ($entrada, $dados) {
            $produtoVariacaoIdNovo = filled($dados['produto_variacao_id'] ?? null) ? (int) $dados['produto_variacao_id'] : null;
            $produtoVariacaoIdAntigo = filled($entrada->produto_variacao_id) ? (int) $entrada->produto_variacao_id : null;
            $produtoMudou = (int) $dados['produto_id'] !== (int) $entrada->produto_id
                || $produtoVariacaoIdNovo !== $produtoVariacaoIdAntigo;

            if ($produtoMudou) {
                $this->reverterQuantidade($entrada->cd_id, $entrada->produto_id, $entrada->produto_variacao_id, $entrada->quantidade);

                $estoque = $this->localizarOuCriarEstoque($entrada->cd_id, $dados['produto_id'], $produtoVariacaoIdNovo);
                $estoque->quantidade_atual += $dados['quantidade'];
            } else {
                $estoque = $this->localizarOuCriarEstoque($entrada->cd_id, $entrada->produto_id, $entrada->produto_variacao_id);
                $diferenca = $dados['quantidade'] - $entrada->quantidade;

                if ($diferenca < 0 && $estoque->quantidade_atual < abs($diferenca)) {
                    throw new EstoqueInsuficienteException($estoque->quantidade_atual, abs($diferenca));
                }

                $estoque->quantidade_atual += $diferenca;
            }

            $estoque->ultima_entrada_at = now();
            $estoque->save();

            $entrada->update($dados);

            return $entrada;
        });
    }

    /**
     * Cancela a entrada e remove a quantidade correspondente do estoque.
     * Não exclui o registro — mantém o histórico com o motivo e quem cancelou.
     *
     * @throws EntradaCanceladaException
     * @throws EstoqueInsuficienteException
     */
    public function cancelarEntrada(Entrada $entrada, int $usuarioId, string $motivo): Entrada
    {
        if ($entrada->status === StatusEntrada::Cancelada) {
            throw new EntradaCanceladaException;
        }

        return DB::transaction(function () use ($entrada, $usuarioId, $motivo) {
            $this->reverterQuantidade($entrada->cd_id, $entrada->produto_id, $entrada->produto_variacao_id, $entrada->quantidade);

            $entrada->update([
                'status' => StatusEntrada::Cancelada,
                'motivo_cancelamento' => $motivo,
                'cancelado_por' => $usuarioId,
                'cancelado_em' => now(),
            ]);

            return $entrada;
        });
    }

    /**
     * @throws EstoqueInsuficienteException
     */
    public function registrarSaida(array $dados): Saida
    {
        return DB::transaction(function () use ($dados) {
            $estoque = $this->localizarOuCriarEstoque(
                $dados['cd_id'],
                $dados['produto_id'],
                $dados['produto_variacao_id'] ?? null,
            );

            if ($estoque->quantidade_atual < $dados['quantidade']) {
                throw new EstoqueInsuficienteException($estoque->quantidade_atual, $dados['quantidade']);
            }

            $situacaoAntes = $estoque->situacao();

            $estoque->quantidade_atual -= $dados['quantidade'];
            $estoque->ultima_saida_at = now();
            $estoque->save();

            $saida = Saida::create($dados);

            if ($situacaoAntes !== SituacaoEstoque::Critico && $estoque->situacao() === SituacaoEstoque::Critico) {
                $this->notificarEstoqueMinimo($estoque);
            }

            return $saida;
        });
    }

    /**
     * Recalcula o estoque para refletir a saída editada: devolve a
     * quantidade antiga do produto/variação originais e debita a nova
     * quantidade do produto/variação atuais (podem ser os mesmos).
     *
     * @throws SaidaCanceladaException
     * @throws EstoqueInsuficienteException
     */
    public function atualizarSaida(Saida $saida, array $dados): Saida
    {
        if ($saida->status === StatusSaida::Cancelada) {
            throw new SaidaCanceladaException;
        }

        return DB::transaction(function () use ($saida, $dados) {
            $this->devolverQuantidade($saida->cd_id, $saida->produto_id, $saida->produto_variacao_id, $saida->quantidade);

            $estoque = $this->localizarOuCriarEstoque($saida->cd_id, $dados['produto_id'], $dados['produto_variacao_id'] ?? null);

            if ($estoque->quantidade_atual < $dados['quantidade']) {
                throw new EstoqueInsuficienteException($estoque->quantidade_atual, $dados['quantidade']);
            }

            $estoque->quantidade_atual -= $dados['quantidade'];
            $estoque->ultima_saida_at = now();
            $estoque->save();

            $saida->update($dados);

            return $saida;
        });
    }

    /**
     * Cancela a saída e devolve a quantidade correspondente ao estoque.
     * Não exclui o registro — mantém o histórico com o motivo e quem cancelou.
     *
     * @throws SaidaCanceladaException
     */
    public function cancelarSaida(Saida $saida, int $usuarioId, string $motivo): Saida
    {
        if ($saida->status === StatusSaida::Cancelada) {
            throw new SaidaCanceladaException;
        }

        return DB::transaction(function () use ($saida, $usuarioId, $motivo) {
            $this->devolverQuantidade($saida->cd_id, $saida->produto_id, $saida->produto_variacao_id, $saida->quantidade);

            $saida->update([
                'status' => StatusSaida::Cancelada,
                'motivo_cancelamento' => $motivo,
                'cancelado_por' => $usuarioId,
                'cancelado_em' => now(),
            ]);

            return $saida;
        });
    }

    /**
     * Ajuste gerado por uma contagem de inventário (Módulo 5): aplica a
     * diferença encontrada (pode ser positiva ou negativa) e registra o
     * movimento correspondente com `origem` apontando para quem pediu o
     * ajuste — sem depender do módulo de Inventário diretamente.
     */
    public function registrarAjusteInventario(
        int $cdId,
        int $produtoId,
        ?int $produtoVariacaoId,
        int $diferenca,
        int $usuarioId,
        Model $origem,
    ): Entrada|Saida|null {
        if ($diferenca === 0) {
            return null;
        }

        return DB::transaction(function () use ($cdId, $produtoId, $produtoVariacaoId, $diferenca, $usuarioId, $origem) {
            $estoque = $this->localizarOuCriarEstoque($cdId, $produtoId, $produtoVariacaoId);
            $situacaoAntes = $estoque->situacao();

            $estoque->quantidade_atual = max(0, $estoque->quantidade_atual + $diferenca);

            $dadosComuns = [
                'cd_id' => $cdId,
                'produto_id' => $produtoId,
                'produto_variacao_id' => $produtoVariacaoId,
                'quantidade' => abs($diferenca),
                'registrado_por' => $usuarioId,
                'origem_type' => $origem->getMorphClass(),
                'origem_id' => $origem->getKey(),
                'observacoes' => 'Ajuste gerado por contagem de inventário.',
            ];

            if ($diferenca > 0) {
                $estoque->ultima_entrada_at = now();
                $estoque->save();

                $movimento = Entrada::create([
                    ...$dadosComuns,
                    'fornecedor_id' => null,
                    'numero_nota_fiscal' => null,
                    'data_compra' => now()->toDateString(),
                    'data_entrega' => now()->toDateString(),
                    'valor_unitario' => 0,
                    'valor_total' => 0,
                    'responsavel_recebimento_id' => null,
                ]);
            } else {
                $estoque->ultima_saida_at = now();
                $estoque->save();

                $movimento = Saida::create([
                    ...$dadosComuns,
                    'colaborador_id' => null,
                    'liberado_por' => $usuarioId,
                    'motivo_saida_id' => MotivoSaida::ajusteInventario()->id,
                    'status_colaborador_snapshot' => null,
                    'data' => now()->toDateString(),
                    'hora' => now()->format('H:i:s'),
                ]);
            }

            if ($situacaoAntes !== SituacaoEstoque::Critico && $estoque->situacao() === SituacaoEstoque::Critico) {
                $this->notificarEstoqueMinimo($estoque);
            }

            return $movimento;
        });
    }

    /**
     * Anexa a cada item de $estoques os totais movimentados no período
     * informado, como atributos dinâmicos (não persistidos):
     * `total_entradas_periodo` e `total_saidas_periodo`.
     *
     * Considera somente Entradas/Saídas com status Ativa e exclui os
     * registros originados por ajuste de inventário (`origem_type` não nulo).
     */
    public function anexarTotaisDoPeriodo(iterable $estoques, string $dataInicio, string $dataFim): void
    {
        $itens = [...$estoques];

        if ($itens === []) {
            return;
        }

        $totalEntradas = Entrada::query()
            ->withoutGlobalScopes()
            ->where('status', StatusEntrada::Ativa)
            ->whereNull('origem_type')
            ->whereDate('data_entrega', '>=', $dataInicio)
            ->whereDate('data_entrega', '<=', $dataFim)
            ->selectRaw('cd_id, produto_id, produto_variacao_id, SUM(quantidade) as total')
            ->groupBy('cd_id', 'produto_id', 'produto_variacao_id')
            ->get()
            ->keyBy(fn ($linha) => "{$linha->cd_id}-{$linha->produto_id}-{$linha->produto_variacao_id}");

        $totalSaidas = Saida::query()
            ->withoutGlobalScopes()
            ->where('status', StatusSaida::Ativa)
            ->whereNull('origem_type')
            ->whereDate('data', '>=', $dataInicio)
            ->whereDate('data', '<=', $dataFim)
            ->selectRaw('cd_id, produto_id, produto_variacao_id, SUM(quantidade) as total')
            ->groupBy('cd_id', 'produto_id', 'produto_variacao_id')
            ->get()
            ->keyBy(fn ($linha) => "{$linha->cd_id}-{$linha->produto_id}-{$linha->produto_variacao_id}");

        foreach ($itens as $estoque) {
            $chave = "{$estoque->cd_id}-{$estoque->produto_id}-{$estoque->produto_variacao_id}";
            $estoque->total_entradas_periodo = (int) ($totalEntradas->get($chave)->total ?? 0);
            $estoque->total_saidas_periodo = (int) ($totalSaidas->get($chave)->total ?? 0);
        }
    }

    /**
     * @throws EstoqueInsuficienteException
     */
    private function reverterQuantidade(int $cdId, int $produtoId, ?int $produtoVariacaoId, int $quantidade): void
    {
        $estoque = $this->localizarOuCriarEstoque($cdId, $produtoId, $produtoVariacaoId);

        if ($estoque->quantidade_atual < $quantidade) {
            throw new EstoqueInsuficienteException($estoque->quantidade_atual, $quantidade);
        }

        $estoque->quantidade_atual -= $quantidade;
        $estoque->save();
    }

    private function devolverQuantidade(int $cdId, int $produtoId, ?int $produtoVariacaoId, int $quantidade): void
    {
        $estoque = $this->localizarOuCriarEstoque($cdId, $produtoId, $produtoVariacaoId);

        $estoque->quantidade_atual += $quantidade;
        $estoque->save();
    }

    private function localizarOuCriarEstoque(int $cdId, int $produtoId, ?int $produtoVariacaoId): Estoque
    {
        $estoque = Estoque::withoutGlobalScopes()
            ->where('cd_id', $cdId)
            ->where('produto_id', $produtoId)
            ->when(
                $produtoVariacaoId,
                fn ($query) => $query->where('produto_variacao_id', $produtoVariacaoId),
                fn ($query) => $query->whereNull('produto_variacao_id'),
            )
            ->lockForUpdate()
            ->first();

        return $estoque ?? Estoque::create([
            'cd_id' => $cdId,
            'produto_id' => $produtoId,
            'produto_variacao_id' => $produtoVariacaoId,
            'quantidade_atual' => 0,
        ]);
    }

    private function notificarEstoqueMinimo(Estoque $estoque): void
    {
        $destinatarios = User::query()
            ->where('ativo', true)
            ->get()
            ->filter(fn (User $user): bool => $user->cd_id === $estoque->cd_id || $user->can('acessar-todos-cds'))
            ->filter(fn (User $user): bool => $user->can('entradas.manage') || $user->can('saidas.manage') || $user->can('acessar-todos-cds'));

        if ($destinatarios->isNotEmpty()) {
            Notification::send($destinatarios, new EstoqueMinimoAtingido($estoque));
        }
    }
}
