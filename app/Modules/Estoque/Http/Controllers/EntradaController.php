<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Estoque\Http\Requests\StoreEntradaRequest;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Estoque\Support\VariacoesParaFormulario;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntradaController extends Controller
{
    public function __construct(private readonly EstoqueService $estoqueService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Entrada::class);

        $entradas = Entrada::query()
            ->with(['produto', 'produtoVariacao', 'fornecedor', 'centroDistribuicao'])
            ->when($request->filled('produto_id'), fn ($query) => $query->where('produto_id', $request->input('produto_id')))
            ->when($request->filled('data_inicio'), fn ($query) => $query->whereDate('data_entrega', '>=', $request->input('data_inicio')))
            ->when($request->filled('data_fim'), fn ($query) => $query->whereDate('data_entrega', '<=', $request->input('data_fim')))
            ->latest('data_entrega')
            ->paginate(20)
            ->withQueryString();

        return view('entradas.index', [
            'entradas' => $entradas,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Entrada::class);

        return view('entradas.create', $this->formOptions());
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

        return redirect()->route('entradas.index')->with('sucesso', 'Entrada registrada e estoque atualizado com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $user = Auth::user();
        $podeEscolherCd = $user->can('acessar-todos-cds');

        // Quando o usuário pode escolher o CD (admin), carregamos as opções de
        // todos os CDs e deixamos um pouco de JS puro no Blade filtrar
        // fornecedor/colaborador/variação conforme a seleção — sem Livewire/JS
        // framework, só esconder/mostrar <option> no client.
        $produtos = Produto::query()->where('status', 'ativo')->with('variacoes')->orderBy('nome')->get();

        return [
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
            'podeEscolherCd' => $podeEscolherCd,
            'produtos' => $produtos,
            'produtosJson' => VariacoesParaFormulario::mapear($produtos),
            'fornecedores' => ($podeEscolherCd ? Fornecedor::withoutGlobalScopes() : Fornecedor::query())
                ->where('ativo', true)
                ->orderBy('razao_social')
                ->get(['id', 'razao_social', 'cd_id']),
            'colaboradores' => ($podeEscolherCd ? Colaborador::withoutGlobalScopes() : Colaborador::query())
                ->where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cd_id']),
        ];
    }
}
