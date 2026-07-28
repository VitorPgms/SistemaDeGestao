<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Exceptions\EntradaCanceladaException;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
use App\Modules\Estoque\Filament\Pages\Entradas;
use App\Modules\Estoque\Http\Requests\StoreEntradaRequest;
use App\Modules\Estoque\Http\Requests\UpdateEntradaRequest;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Services\EstoqueService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'status' => StatusEntrada::Ativa,
            'responsavel_recebimento_id' => $request->input('responsavel_recebimento_id'),
            'observacoes' => $request->input('observacoes'),
            'registrado_por' => Auth::id(),
        ]);

        Notification::make()->title('Entrada registrada com sucesso.')->success()->send();

        return redirect(Entradas::getUrl());
    }

    public function update(UpdateEntradaRequest $request, Entrada $entrada): RedirectResponse
    {
        $quantidade = (int) $request->input('quantidade');
        $valorUnitario = (float) $request->input('valor_unitario');

        try {
            $this->estoqueService->atualizarEntrada($entrada, [
                'produto_id' => $request->input('produto_id'),
                'produto_variacao_id' => $request->input('produto_variacao_id'),
                'fornecedor_id' => $request->input('fornecedor_id'),
                'numero_nota_fiscal' => $request->input('numero_nota_fiscal'),
                'data_compra' => $request->input('data_compra'),
                'data_entrega' => $request->input('data_entrega'),
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => round($quantidade * $valorUnitario, 2),
                'responsavel_recebimento_id' => $request->input('responsavel_recebimento_id'),
                'observacoes' => $request->input('observacoes'),
            ]);
        } catch (EntradaCanceladaException|EstoqueInsuficienteException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return back()->withInput();
        }

        Notification::make()->title('Entrada atualizada com sucesso.')->success()->send();

        return redirect(Entradas::getUrl());
    }

    public function cancelar(Request $request, Entrada $entrada): RedirectResponse
    {
        $this->authorize('update', $entrada);

        $dados = $request->validate([
            'motivo_cancelamento' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->estoqueService->cancelarEntrada($entrada, Auth::id(), $dados['motivo_cancelamento']);
        } catch (EntradaCanceladaException|EstoqueInsuficienteException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return back();
        }

        Notification::make()->title('Entrada cancelada com sucesso.')->success()->send();

        return redirect(Entradas::getUrl());
    }
}
