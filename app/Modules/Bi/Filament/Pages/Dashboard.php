<?php

namespace App\Modules\Bi\Filament\Pages;

use App\Modules\Core\Concerns\ResolvesPeriodo;
use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\ColaboradorResource;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

/**
 * Page fina: só entrega a mesma view de sempre (dashboard.index) dentro do
 * shell nativo do Filament. Nenhuma regra de negócio mora aqui — a situação
 * do estoque reaproveita Estoque::situacao()/scopes já existentes, e os
 * totais de Entrada/Saída reaproveitam os mesmos filtros (status Ativa,
 * exclusão de ajuste de inventário) já usados em EstoqueService.
 */
class Dashboard extends Page
{
    use ResolvesPeriodo;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    // Sem grupo (nenhum navigationGroup): fica fora de Cadastros/Operações/BI,
    // como item avulso no topo do menu — é a página inicial do sistema.
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.dashboard';

    /**
     * O Filament deriva o nome da rota a partir do basename da classe
     * ("Dashboard"), não do slug/caminho — sem isso colide com o nome da
     * página padrão do Filament (Filament\Pages\Dashboard). O
     * Filament\Pages\Dashboard genérico foi removido do AdminPanelProvider
     * (esta page ocupa a raiz no lugar dele), mas o nome próprio de rota
     * continua evitando qualquer colisão futura.
     */
    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'operacoes-dashboard';
    }

    /**
     * Faz esta page ocupar a raiz do painel ("/admin"), igual ao
     * Filament\Pages\Dashboard nativo faz consigo mesmo — é assim que o
     * Filament identifica a home do painel (getRedirectUrl() navega pelo
     * primeiro item do menu, mas quando uma page ocupa a rota raiz ela é
     * resolvida diretamente, sem depender da ordem dos grupos de navegação).
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Visão geral das operações do Centro de Distribuição.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $podeEscolherCd = $user->can('acessar-todos-cds');
        // Usuário comum nunca escolhe o CD: o valor da URL só é honrado se o
        // usuário já tiver a permissão (checada aqui, não confiada da request).
        // O isolamento em si continua garantido pelo CdScope do BelongsToCd em
        // cada Model (Entrada/Saida/Estoque/Fornecedor), com ou sem esse filtro.
        $cdId = $podeEscolherCd ? request()->integer('cd_id') ?: null : null;

        [$inicio, $fim] = $this->resolverPeriodo();

        $categoriaId = request()->integer('categoria_id') ?: null;
        $produtoId = request()->integer('produto_id') ?: null;
        $fornecedorId = request()->integer('fornecedor_id') ?: null;

        $filtrarPorProduto = fn ($query) => $query
            ->when($categoriaId, fn ($q) => $q->whereHas('produto', fn ($q) => $q->where('categoria_id', $categoriaId)))
            ->when($produtoId, fn ($q) => $q->where('produto_id', $produtoId));

        $estoqueBase = Estoque::query()
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->when($fornecedorId, fn ($q) => $q->where('fornecedor_preferencial_id', $fornecedorId))
            ->tap($filtrarPorProduto);

        $itensCriticos = (clone $estoqueBase)->critico()->count();
        $itensAtencao = (clone $estoqueBase)->atencao()->count();
        $totalItensEstoque = (clone $estoqueBase)->count();
        $itensNormais = max(0, $totalItensEstoque - $itensCriticos - $itensAtencao);
        $quantidadeTotalEstoque = (int) (clone $estoqueBase)->sum('quantidade_atual');

        $totalProdutosCadastrados = Produto::query()
            ->when($categoriaId, fn ($q) => $q->where('categoria_id', $categoriaId))
            ->when($produtoId, fn ($q) => $q->where('id', $produtoId))
            ->count();

        $totalColaboradores = Colaborador::query()
            ->where('status', StatusColaborador::Ativo)
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->count();

        // Usuário sem "acessar-todos-cds" só enxerga o próprio CD (mesma regra
        // do CdScope) — não há query aqui, é o mesmo 1 CD que já delimita todo
        // o resto do dashboard para ele.
        $totalCds = $podeEscolherCd
            ? CentroDistribuicao::query()->when($cdId, fn ($q) => $q->where('id', $cdId))->count()
            : 1;

        // "Vencido" ou "próximo" (dentro dos DIAS_ALERTA_EXAME_PERIODICO) é
        // equivalente, em SQL, a data_proximo_exame_periodico <= hoje + N dias
        // — mesma janela usada por Colaborador::statusExamePeriodico() e pelo
        // filtro "Exame vencido ou próximo do vencimento" na listagem de
        // Colaboradores. Ordenar por essa data já entrega a prioridade certa:
        // vencidos há mais tempo (datas mais antigas) primeiro, depois os
        // que vencem mais cedo.
        $examesPeriodicos = Colaborador::query()
            ->where('status', StatusColaborador::Ativo)
            ->whereNotNull('data_proximo_exame_periodico')
            ->whereDate('data_proximo_exame_periodico', '<=', now()->addDays(Colaborador::DIAS_ALERTA_EXAME_PERIODICO))
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->with('centroDistribuicao')
            ->orderBy('data_proximo_exame_periodico')
            ->get();

        $proximoExamePeriodico = $examesPeriodicos->first();
        $colaboradoresExameAlerta = $examesPeriodicos->count();
        $examesPeriodicosTop5 = $examesPeriodicos->take(5);

        // O gráfico/cards representam a movimentação efetiva no estoque, por
        // isso usam created_at (quando a entrada foi de fato registrada) e
        // não data_entrega (data de negócio informada manualmente, que pode
        // divergir da data em que o estoque foi movimentado).
        $entradasBase = Entrada::query()
            ->where('status', StatusEntrada::Ativa)
            ->whereNull('origem_type')
            ->whereBetween('created_at', [$inicio, $fim])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->when($fornecedorId, fn ($q) => $q->where('fornecedor_id', $fornecedorId))
            ->tap($filtrarPorProduto);
        $entradasQuantidade = (int) (clone $entradasBase)->sum('quantidade');
        $entradasValor = (float) (clone $entradasBase)->sum('valor_total');
        $entradasRegistros = (clone $entradasBase)->count();
        $produtosRecebidos = (clone $entradasBase)->distinct('produto_id')->count('produto_id');

        // Não usa o período do filtro: são entregas com data_entrega futura,
        // independente de quando o registro foi cadastrado (created_at).
        $proximasEntradas = Entrada::query()
            ->where('status', StatusEntrada::Ativa)
            ->whereNull('origem_type')
            ->whereDate('data_entrega', '>=', now())
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->when($fornecedorId, fn ($q) => $q->where('fornecedor_id', $fornecedorId))
            ->tap($filtrarPorProduto)
            ->with(['produto', 'produtoVariacao', 'fornecedor'])
            ->orderBy('data_entrega')
            ->limit(5)
            ->get();

        $saidasBase = Saida::query()
            ->where('status', StatusSaida::Ativa)
            ->whereNull('origem_type')
            ->whereBetween('data', [$inicio, $fim])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->tap($filtrarPorProduto);
        $saidasQuantidade = (int) (clone $saidasBase)->sum('quantidade');
        $saidasRegistros = (clone $saidasBase)->count();
        $saldoMovimentacao = $entradasQuantidade - $saidasQuantidade;

        $entradasPorDia = (clone $entradasBase)
            ->selectRaw('DATE(created_at) as dia, SUM(quantidade) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $saidasPorDia = (clone $saidasBase)
            ->selectRaw('data as dia, SUM(quantidade) as total')
            ->groupBy('data')
            ->pluck('total', 'dia');

        $labels = [];
        $serieEntradas = [];
        $serieSaidas = [];
        for ($dia = $inicio->copy()->startOfDay(); $dia->lte($fim); $dia->addDay()) {
            $chave = $dia->format('Y-m-d');
            $labels[] = $dia->format('d/m');
            $serieEntradas[] = (int) ($entradasPorDia[$chave] ?? 0);
            $serieSaidas[] = (int) ($saidasPorDia[$chave] ?? 0);
        }

        $alertasEstoque = (clone $estoqueBase)->with(['produto', 'produtoVariacao', 'centroDistribuicao'])->critico()->get()
            ->concat((clone $estoqueBase)->with(['produto', 'produtoVariacao', 'centroDistribuicao'])->atencao()->get())
            ->values();

        $maisMovimentados = (clone $saidasBase)
            ->selectRaw('produto_id, SUM(quantidade) as total_saida')
            ->groupBy('produto_id')
            ->orderByDesc('total_saida')
            ->limit(5)
            ->get()
            ->map(function ($linha) {
                $produto = Produto::with('categoria')->find($linha->produto_id);

                return [
                    'produto' => $produto?->nome ?? '—',
                    'categoria' => $produto?->categoria?->nome ?? '—',
                    'quantidade' => (int) $linha->total_saida,
                ];
            });

        // Mesma regra de "movimentação real" usada em $entradasBase/$saidasBase:
        // sem isso, uma Entrada/Saída cancelada apareceria aqui como se fosse
        // uma movimentação válida.
        $entradasRecentes = Entrada::query()
            ->where('status', StatusEntrada::Ativa)
            ->whereNull('origem_type')
            ->with(['produto', 'produtoVariacao', 'fornecedor', 'centroDistribuicao'])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->latest('created_at')
            ->take(8)
            ->get();

        $saidasRecentes = Saida::query()
            ->where('status', StatusSaida::Ativa)
            ->whereNull('origem_type')
            ->with(['produto', 'produtoVariacao', 'colaborador', 'centroDistribuicao'])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->latest('created_at')
            ->take(8)
            ->get();

        return [
            'podeEscolherCd' => $podeEscolherCd,
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
            'cdSelecionado' => $cdId,
            'periodoSelecionado' => request()->input('periodo', 'este_mes'),
            'dataInicio' => $inicio->format('Y-m-d'),
            'dataFim' => $fim->format('Y-m-d'),
            'categorias' => Categoria::query()->orderBy('nome')->pluck('nome', 'id'),
            'categoriaSelecionada' => $categoriaId,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
            'produtoSelecionado' => $produtoId,
            'fornecedores' => ($podeEscolherCd ? Fornecedor::withoutGlobalScopes() : Fornecedor::query())->orderBy('razao_social')->pluck('razao_social', 'id'),
            'fornecedorSelecionado' => $fornecedorId,
            'totalProdutosCadastrados' => $totalProdutosCadastrados,
            'totalColaboradores' => $totalColaboradores,
            'totalCds' => $totalCds,
            'proximoExamePeriodico' => $proximoExamePeriodico,
            'colaboradoresExameAlerta' => $colaboradoresExameAlerta,
            'examesPeriodicosTop5' => $examesPeriodicosTop5,
            'urlExamesPeriodicos' => ColaboradorResource::getUrl('index', [
                'filters' => ['exame_periodico' => ['isActive' => true]],
                'sort' => 'data_proximo_exame_periodico:asc',
            ]),
            'quantidadeTotalEstoque' => $quantidadeTotalEstoque,
            'itensCriticos' => $itensCriticos,
            'itensAtencao' => $itensAtencao,
            'itensNormais' => $itensNormais,
            'entradasQuantidade' => $entradasQuantidade,
            'entradasValor' => $entradasValor,
            'entradasRegistros' => $entradasRegistros,
            'produtosRecebidos' => $produtosRecebidos,
            'proximasEntradas' => $proximasEntradas,
            'saidasQuantidade' => $saidasQuantidade,
            'saidasRegistros' => $saidasRegistros,
            'saldoMovimentacao' => $saldoMovimentacao,
            'graficoLabels' => $labels,
            'graficoEntradas' => $serieEntradas,
            'graficoSaidas' => $serieSaidas,
            'alertasEstoque' => $alertasEstoque,
            'maisMovimentados' => $maisMovimentados,
            'entradasRecentes' => $entradasRecentes,
            'saidasRecentes' => $saidasRecentes,
            'notificacoes' => $user->unreadNotifications()->latest()->take(5)->get(),
        ];
    }
}
