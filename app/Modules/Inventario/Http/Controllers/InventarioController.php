<?php

namespace App\Modules\Inventario\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Exceptions\InventarioIncompletoException;
use App\Modules\Inventario\Http\Requests\StoreInventarioRequest;
use App\Modules\Inventario\Models\Inventario;
use App\Modules\Inventario\Services\InventarioService;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Inventario::class);

        $inventarios = Inventario::query()
            ->with(['responsavel', 'centroDistribuicao'])
            ->withCount('itens')
            ->latest('data_contagem')
            ->paginate(20);

        return view('inventarios.index', ['inventarios' => $inventarios]);
    }

    public function create(): View
    {
        $this->authorize('create', Inventario::class);

        $user = Auth::user();
        $podeEscolherCd = $user->can('acessar-todos-cds');

        return view('inventarios.create', [
            'podeEscolherCd' => $podeEscolherCd,
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
        ]);
    }

    public function store(StoreInventarioRequest $request): RedirectResponse
    {
        $inventario = $this->inventarioService->abrir(
            $request->resolvedCdId(),
            Auth::id(),
            $request->input('data_contagem'),
            $request->input('observacoes'),
        );

        return redirect()->route('inventarios.show', $inventario)
            ->with('sucesso', 'Inventário aberto. Registre a contagem física de cada item.');
    }

    public function show(Inventario $inventario): View
    {
        $this->authorize('view', $inventario);

        $itens = $inventario->itens()->with(['produto', 'produtoVariacao'])->orderBy('produto_id')->get();

        return view('inventarios.show', [
            'inventario' => $inventario,
            'itens' => $itens,
        ]);
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

        return redirect()->route('inventarios.show', $inventario)
            ->with('sucesso', 'Inventário finalizado. Estoque ajustado conforme a contagem.');
    }

    public function cancelar(Inventario $inventario): RedirectResponse
    {
        $this->authorize('update', $inventario);

        $this->inventarioService->cancelar($inventario);

        return redirect()->route('inventarios.index')->with('sucesso', 'Inventário cancelado.');
    }
}
