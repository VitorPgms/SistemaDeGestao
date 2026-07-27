<?php

namespace App\Modules\Estoque\Services;

use App\Models\User;
use App\Modules\Estoque\Enums\SituacaoEstoque;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
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
                    'colaborador_recebimento_id' => null,
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
