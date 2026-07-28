<?php

namespace App\Modules\Bi\Filament\Pages;

use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use BackedEnum;
use Filament\Panel;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (dashboard.index) dentro do
 * shell nativo do Filament. Nenhuma regra de negócio mora aqui — é a mesma
 * lógica de consulta que já existia no DashboardController::index().
 */
class Dashboard extends Page
{
    protected static ?string $slug = 'dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'BI';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.dashboard';

    /**
     * O Filament deriva o nome da rota a partir do basename da classe
     * ("Dashboard"), não do slug/caminho — sem isso colide com o nome da
     * página padrão do Filament (Filament\Pages\Dashboard, em admin/).
     */
    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'operacoes-dashboard';
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
        $cdId = $podeEscolherCd ? request()->integer('cd_id') ?: null : null;

        $inicio = request()->filled('data_inicio')
            ? Carbon::parse(request()->input('data_inicio'))->startOfDay()
            : now()->startOfMonth();
        $fim = request()->filled('data_fim')
            ? Carbon::parse(request()->input('data_fim'))->endOfDay()
            : now()->endOfMonth();

        $estoqueBase = Estoque::query()->when($cdId, fn ($q) => $q->where('cd_id', $cdId));
        $itensCriticos = (clone $estoqueBase)->critico()->count();
        $itensAtencao = (clone $estoqueBase)->atencao()->count();

        $entradasBase = Entrada::query()
            ->whereBetween('data_entrega', [$inicio, $fim])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId));
        $entradasQuantidade = (int) (clone $entradasBase)->sum('quantidade');
        $entradasValor = (float) (clone $entradasBase)->sum('valor_total');

        $saidasBase = Saida::query()
            ->whereBetween('data', [$inicio, $fim])
            ->when($cdId, fn ($q) => $q->where('cd_id', $cdId));
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
            'dataInicio' => $inicio->format('Y-m-d'),
            'dataFim' => $fim->format('Y-m-d'),
            'itensCriticos' => $itensCriticos,
            'itensAtencao' => $itensAtencao,
            'entradasQuantidade' => $entradasQuantidade,
            'entradasValor' => $entradasValor,
            'saidasQuantidade' => $saidasQuantidade,
            'graficoLabels' => $labels,
            'graficoEntradas' => $serieEntradas,
            'graficoSaidas' => $serieSaidas,
            'ultimasMovimentacoes' => $ultimasMovimentacoes,
            'notificacoes' => $user->unreadNotifications()->latest()->take(5)->get(),
        ];
    }
}
