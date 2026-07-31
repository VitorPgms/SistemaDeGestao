<?php

namespace App\Modules\Bi\Filament\Pages;

use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use BackedEnum;
use Filament\Panel;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
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

        // "Quanto foi comprado/saiu no período": mesma regra de negócio já
        // usada em EstoqueService::anexarTotaisDoPeriodo (status Ativa,
        // exclui ajuste de inventário) — não duplicada, só reaplicada aqui
        // com os mesmos dois filtros, para o card bater com a tela de Estoque.
        $entradasBase = Entrada::query()
            ->where('status', StatusEntrada::Ativa)
            ->whereNull('origem_type')
            ->whereBetween('data_entrega', [$inicio, $fim])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->when($fornecedorId, fn ($q) => $q->where('fornecedor_id', $fornecedorId))
            ->tap($filtrarPorProduto);
        $entradasQuantidade = (int) (clone $entradasBase)->sum('quantidade');
        $entradasValor = (float) (clone $entradasBase)->sum('valor_total');

        $saidasBase = Saida::query()
            ->where('status', StatusSaida::Ativa)
            ->whereNull('origem_type')
            ->whereBetween('data', [$inicio, $fim])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->tap($filtrarPorProduto);
        $saidasQuantidade = (int) (clone $saidasBase)->sum('quantidade');

        $entradasPorDia = (clone $entradasBase)
            ->selectRaw('data_entrega as dia, SUM(quantidade) as total')
            ->groupBy('data_entrega')
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

        $alertasEstoque = (clone $estoqueBase)->with(['produto', 'produtoVariacao'])->critico()->get()
            ->concat((clone $estoqueBase)->with(['produto', 'produtoVariacao'])->atencao()->get())
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

        $ultimasEntradas = Entrada::query()
            ->with(['produto', 'produtoVariacao'])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->latest('created_at')
            ->take(8)
            ->get()
            ->map(fn (Entrada $entrada) => [
                'tipo' => 'Entrada',
                'produto' => $entrada->produto->nome.($entrada->produtoVariacao ? " ({$entrada->produtoVariacao->valor})" : ''),
                'quantidade' => $entrada->quantidade,
                'data' => $entrada->data_entrega,
                'criado_em' => $entrada->created_at,
            ]);

        $ultimasSaidas = Saida::query()
            ->with(['produto', 'produtoVariacao'])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
            ->latest('created_at')
            ->take(8)
            ->get()
            ->map(fn (Saida $saida) => [
                'tipo' => 'Saída',
                'produto' => $saida->produto->nome.($saida->produtoVariacao ? " ({$saida->produtoVariacao->valor})" : ''),
                'quantidade' => $saida->quantidade,
                'data' => $saida->data,
                'criado_em' => $saida->created_at,
            ]);

        $ultimasMovimentacoes = $ultimasEntradas->concat($ultimasSaidas)
            ->sortByDesc('criado_em')
            ->take(8)
            ->values();

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
            'quantidadeTotalEstoque' => $quantidadeTotalEstoque,
            'itensCriticos' => $itensCriticos,
            'itensAtencao' => $itensAtencao,
            'itensNormais' => $itensNormais,
            'entradasQuantidade' => $entradasQuantidade,
            'entradasValor' => $entradasValor,
            'saidasQuantidade' => $saidasQuantidade,
            'graficoLabels' => $labels,
            'graficoEntradas' => $serieEntradas,
            'graficoSaidas' => $serieSaidas,
            'alertasEstoque' => $alertasEstoque,
            'maisMovimentados' => $maisMovimentados,
            'ultimasMovimentacoes' => $ultimasMovimentacoes,
            'notificacoes' => $user->unreadNotifications()->latest()->take(5)->get(),
        ];
    }

    /**
     * Opções práticas de período (não é um seletor de datas livre por
     * padrão): hoje, últimos 7/30 dias, este mês, mês anterior, ou
     * personalizado (usa data_inicio/data_fim da query string).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverPeriodo(): array
    {
        $periodo = request()->input('periodo', 'este_mes');

        return match ($periodo) {
            'hoje' => [now()->startOfDay(), now()->endOfDay()],
            '7dias' => [now()->copy()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30dias' => [now()->copy()->subDays(29)->startOfDay(), now()->endOfDay()],
            'mes_anterior' => [now()->copy()->subMonthNoOverflow()->startOfMonth(), now()->copy()->subMonthNoOverflow()->endOfMonth()],
            'personalizado' => [
                request()->filled('data_inicio') ? Carbon::parse(request()->input('data_inicio'))->startOfDay() : now()->startOfMonth(),
                request()->filled('data_fim') ? Carbon::parse(request()->input('data_fim'))->endOfDay() : now()->endOfMonth(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
