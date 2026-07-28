<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\Filament\Pages\Entradas;
use App\Modules\Estoque\Http\Requests\StoreEntradaRequest;
use App\Modules\Estoque\Services\EstoqueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EntradaController extends Controller
{
    public function __construct(private readonly EstoqueService $estoqueService)
    {
    }

    public function store(StoreEntradaRequest $request): RedirectResponse
    {
        $cdId = $request->resolvedCdId();
        $quantidade = (int) $request->input('quantidade');
        $valorUnitario = (float) $request->input('valor_unitario');

        $this->estoqueService->registrarEntrada([
            'cd_id' => $cdId,
            'produto_id' => $request->input('produto_id'),
            'produto_variacao_id' => $request->input('produto_variacao_id'),
            'fornecedor_id' => $request->input('fornecedor_id'),
            'numero_nota_fiscal' => $request->input('numero_nota_fiscal'),
            'data_compra' => $request->input('data_compra'),
            'data_entrega' => $request->input('data_entrega'),
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'valor_total' => round($quantidade * $valorUnitario, 2),
            'colaborador_recebimento_id' => $request->input('colaborador_recebimento_id'),
            'observacoes' => $request->input('observacoes'),
            'registrado_por' => Auth::id(),
        ]);

        return redirect(Entradas::getUrl())->with('sucesso', 'Entrada registrada e estoque atualizado com sucesso.');
    }
}
