<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
use App\Modules\Estoque\Http\Requests\StoreSaidaRequest;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Estoque\Support\VariacoesParaFormulario;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaidaController extends Controller
{
    public function __construct(private readonly EstoqueService $estoqueService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Saida::class);

        $saidas = Saida::query()
            ->with(['produto', 'produtoVariacao', 'colaborador', 'motivoSaida', 'centroDistribuicao'])
            ->when($request->filled('produto_id'), fn ($query) => $query->where('produto_id', $request->input('produto_id')))
            ->when($request->filled('data_inicio'), fn ($query) => $query->whereDate('data', '>=', $request->input('data_inicio')))
            ->when($request->filled('data_fim'), fn ($query) => $query->whereDate('data', '<=', $request->input('data_fim')))
            ->latest('data')
            ->paginate(20)
            ->withQueryString();

        return view('saidas.index', [
            'saidas' => $saidas,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Saida::class);

        return view('saidas.create', $this->formOptions());
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

        return redirect()->route('saidas.index')->with('sucesso', 'Saída registrada e estoque atualizado com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $user = Auth::user();
        $podeEscolherCd = $user->can('acessar-todos-cds');

        $produtos = Produto::query()->where('status', 'ativo')->with('variacoes')->orderBy('nome')->get();

        return [
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
            'podeEscolherCd' => $podeEscolherCd,
            'produtos' => $produtos,
            'produtosJson' => VariacoesParaFormulario::mapear($produtos),
            'colaboradores' => ($podeEscolherCd ? Colaborador::withoutGlobalScopes() : Colaborador::query())
                ->where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cd_id']),
            // User não tem CdScope (ver App\Models\User), então o filtro por CD
            // aqui precisa ser explícito, diferente dos outros models do módulo.
            'usuarios' => User::query()
                ->where('ativo', true)
                ->when(! $podeEscolherCd, fn ($query) => $query->where('cd_id', $user->cd_id))
                ->orderBy('name')
                ->get(['id', 'name', 'cd_id']),
            'motivos' => MotivoSaida::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id'),
        ];
    }
}
