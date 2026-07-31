<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
use App\Modules\Estoque\Exceptions\SaidaCanceladaException;
use App\Modules\Estoque\Filament\Pages\Saidas;
use App\Modules\Estoque\Http\Requests\StoreSaidaRequest;
use App\Modules\Estoque\Http\Requests\UpdateSaidaRequest;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Organizacional\Models\Colaborador;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaidaController extends Controller
{
    public function __construct(private readonly EstoqueService $estoqueService)
    {
    }

    public function store(StoreSaidaRequest $request): RedirectResponse
    {
        $cdId = $request->resolvedCdId();

        $colaborador = Colaborador::withoutGlobalScopes()->findOrFail($request->input('colaborador_id'));

        try {
            $this->estoqueService->registrarSaida([
                'cd_id' => $cdId,
                'produto_id' => $request->input('produto_id'),
                'produto_variacao_id' => $request->input('produto_variacao_id'),
                'quantidade' => (int) $request->input('quantidade'),
                'colaborador_id' => $colaborador->id,
                'liberado_por' => $request->input('liberado_por'),
                'motivo_saida_id' => $request->input('motivo_saida_id'),
                'status_colaborador_snapshot' => $colaborador->status,
                'data' => $request->input('data'),
                'hora' => $request->input('hora'),
                'observacoes' => $request->input('observacoes'),
                'registrado_por' => Auth::id(),
            ]);
        } catch (EstoqueInsuficienteException $exception) {
            return back()->withInput()->withErrors(['quantidade' => $exception->getMessage()]);
        }

        return redirect(Saidas::getUrl())->with('sucesso', 'Saída registrada e estoque atualizado com sucesso.');
    }

    public function update(UpdateSaidaRequest $request, Saida $saida): RedirectResponse
    {
        $colaborador = Colaborador::withoutGlobalScopes()->findOrFail($request->input('colaborador_id'));

        try {
            $this->estoqueService->atualizarSaida($saida, [
                'produto_id' => $request->input('produto_id'),
                'produto_variacao_id' => $request->input('produto_variacao_id'),
                'quantidade' => (int) $request->input('quantidade'),
                'colaborador_id' => $colaborador->id,
                'liberado_por' => $request->input('liberado_por'),
                'motivo_saida_id' => $request->input('motivo_saida_id'),
                'status_colaborador_snapshot' => $colaborador->status,
                'data' => $request->input('data'),
                'hora' => $request->input('hora'),
                'observacoes' => $request->input('observacoes'),
            ]);
        } catch (SaidaCanceladaException|EstoqueInsuficienteException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return back()->withInput();
        }

        Notification::make()->title('Saída atualizada com sucesso.')->success()->send();

        return redirect(Saidas::getUrl());
    }

    public function cancelar(Request $request, Saida $saida): RedirectResponse
    {
        $this->authorize('update', $saida);

        $dados = $request->validate([
            'motivo_cancelamento' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->estoqueService->cancelarSaida($saida, Auth::id(), $dados['motivo_cancelamento']);
        } catch (SaidaCanceladaException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return back();
        }

        Notification::make()->title('Saída cancelada com sucesso.')->success()->send();

        return redirect(Saidas::getUrl());
    }
}
