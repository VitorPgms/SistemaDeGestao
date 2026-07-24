<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Produto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Estoque::class);

        $estoques = Estoque::query()
            ->with(['produto', 'produtoVariacao', 'centroDistribuicao', 'fornecedorPreferencial'])
            ->when($request->filled('produto_id'), fn ($query) => $query->where('produto_id', $request->input('produto_id')))
            ->orderBy('produto_id')
            ->paginate(20)
            ->withQueryString();

        return view('estoque.index', [
            'estoques' => $estoques,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function edit(Estoque $estoque): View
    {
        $this->authorize('update', $estoque);

        return view('estoque.edit', ['estoque' => $estoque->load(['produto', 'produtoVariacao'])]);
    }

    public function update(Request $request, Estoque $estoque): RedirectResponse
    {
        $this->authorize('update', $estoque);

        $dados = $request->validate([
            'quantidade_minima' => ['required', 'integer', 'min:0'],
            'quantidade_ideal' => ['required', 'integer', 'min:0', 'gte:quantidade_minima'],
            'localizacao' => ['nullable', 'string', 'max:255'],
        ]);

        $estoque->update($dados);

        return redirect()->route('estoque.index')->with('sucesso', 'Parâmetros de estoque atualizados com sucesso.');
    }
}
