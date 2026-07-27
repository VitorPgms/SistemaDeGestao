<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Estoque\Enums\TipoVariacao;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Inventario\Enums\StatusInventario;
use App\Modules\Inventario\Exceptions\InventarioIncompletoException;
use App\Modules\Inventario\Models\Inventario;
use App\Modules\Inventario\Models\InventarioItem;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    public function __construct(private readonly EstoqueService $estoqueService)
    {
    }

    /**
     * Abre um inventário e tira um "retrato" da quantidade de sistema de
     * todos os produtos ativos (e suas variações) naquele CD — inclusive os
     * que nunca tiveram estoque lançado (ficam com quantidade_sistema = 0).
     */
    public function abrir(int $cdId, int $responsavelId, string $dataContagem, ?string $observacoes): Inventario
    {
        return DB::transaction(function () use ($cdId, $responsavelId, $dataContagem, $observacoes) {
            $inventario = Inventario::create([
                'cd_id' => $cdId,
                'responsavel_id' => $responsavelId,
                'data_contagem' => $dataContagem,
                'status' => StatusInventario::EmAndamento,
                'observacoes' => $observacoes,
            ]);

            $quantidadesAtuais = Estoque::withoutGlobalScopes()
                ->where('cd_id', $cdId)
                ->get()
                ->keyBy(fn (Estoque $estoque) => $estoque->produto_id.'-'.($estoque->produto_variacao_id ?? 'null'));

            $produtos = Produto::query()->where('status', 'ativo')->with('variacoes')->get();

            foreach ($produtos as $produto) {
                if ($produto->tipo_variacao === TipoVariacao::Nenhum) {
                    $this->criarItem($inventario, $produto->id, null, $quantidadesAtuais);

                    continue;
                }

                foreach ($produto->variacoes as $variacao) {
                    $this->criarItem($inventario, $produto->id, $variacao->id, $quantidadesAtuais);
                }
            }

            return $inventario;
        });
    }

    /**
     * @param  array<int, int|null>  $quantidadesContadas  chave: inventario_item_id
     */
    public function salvarContagem(Inventario $inventario, array $quantidadesContadas): void
    {
        foreach ($quantidadesContadas as $itemId => $quantidade) {
            if ($quantidade === null || $quantidade === '') {
                continue;
            }

            InventarioItem::query()
                ->where('inventario_id', $inventario->id)
                ->where('id', $itemId)
                ->update(['quantidade_contada' => $quantidade]);
        }
    }

    /**
     * @throws InventarioIncompletoException
     */
    public function finalizar(Inventario $inventario, int $usuarioId): Inventario
    {
        return DB::transaction(function () use ($inventario, $usuarioId) {
            if ($inventario->itens()->whereNull('quantidade_contada')->exists()) {
                throw new InventarioIncompletoException;
            }

            foreach ($inventario->itens as $item) {
                $diferenca = $item->divergencia();

                if ($diferenca !== 0) {
                    $this->estoqueService->registrarAjusteInventario(
                        $inventario->cd_id,
                        $item->produto_id,
                        $item->produto_variacao_id,
                        $diferenca,
                        $usuarioId,
                        $inventario,
                    );
                }
            }

            $inventario->update(['status' => StatusInventario::Concluido]);

            return $inventario;
        });
    }

    public function cancelar(Inventario $inventario): Inventario
    {
        $inventario->update(['status' => StatusInventario::Cancelado]);

        return $inventario;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Estoque>  $quantidadesAtuais
     */
    private function criarItem(Inventario $inventario, int $produtoId, ?int $variacaoId, $quantidadesAtuais): void
    {
        $chave = $produtoId.'-'.($variacaoId ?? 'null');

        InventarioItem::create([
            'inventario_id' => $inventario->id,
            'produto_id' => $produtoId,
            'produto_variacao_id' => $variacaoId,
            'quantidade_sistema' => $quantidadesAtuais[$chave]->quantidade_atual ?? 0,
        ]);
    }
}
