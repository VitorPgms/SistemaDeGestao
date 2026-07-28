<?php

namespace App\Modules\Inventario\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Exceptions\InventarioIncompletoException;
use App\Modules\Inventario\Filament\Pages\Inventarios;
use App\Modules\Inventario\Filament\Pages\InventarioDetalhe;
use App\Modules\Inventario\Http\Requests\StoreInventarioRequest;
use App\Modules\Inventario\Models\Inventario;
use App\Modules\Inventario\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    public function store(StoreInventarioRequest $request): RedirectResponse
    {
        $inventario = $this->inventarioService->abrir(
            $request->resolvedCdId(),
            Auth::id(),
            $request->input('data_contagem'),
            $request->input('observacoes'),
        );

        return redirect(InventarioDetalhe::getUrl(['inventario' => $inventario]))
            ->with('sucesso', 'Inventário aberto. Registre a contagem física de cada item.');
    }

    public function salvarContagem(Request $request, Inventario $inventario): RedirectResponse
    {
        $this->authorize('update', $inventario);

        $dados = $request->validate([
            'quantidades' => ['required', 'array'],
            'quantidades.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->inventarioService->salvarContagem($inventario, $dados['quantidades']);

        return back()->with('sucesso', 'Contagem salva.');
    }

    public function finalizar(Inventario $inventario): RedirectResponse
    {
        $this->authorize('update', $inventario);

        try {
            $this->inventarioService->finalizar($inventario, Auth::id());
        } catch (InventarioIncompletoException $exception) {
            return back()->with('erro', $exception->getMessage());
        }

        return redirect(InventarioDetalhe::getUrl(['inventario' => $inventario]))
            ->with('sucesso', 'Inventário finalizado. Estoque ajustado conforme a contagem.');
    }

    public function cancelar(Inventario $inventario): RedirectResponse
    {
        $this->authorize('update', $inventario);

        $this->inventarioService->cancelar($inventario);

        return redirect(Inventarios::getUrl())->with('sucesso', 'Inventário cancelado.');
    }
}
