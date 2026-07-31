<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (saidas.index) dentro do
 * shell nativo do Filament. Regra de negócio continua em SaidaController
 * (store/update/cancelar) e EstoqueService.
 */
class Saidas extends Page
{
    protected static ?string $slug = 'saidas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpOnSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected static ?string $navigationLabel = 'Saídas';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Saídas';

    protected string $view = 'filament.pages.saidas';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('saidas.view') ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Histórico de retiradas de mercadoria.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $podeEscolherCd = $user->can('acessar-todos-cds');
        $status = request()->input('status', 'ativa');

        $saidas = Saida::query()
            ->with(['produto', 'produtoVariacao', 'colaborador', 'motivoSaida', 'canceladoPor', 'centroDistribuicao'])
            ->when($status === 'ativa', fn ($query) => $query->where('status', StatusSaida::Ativa))
            ->when($status === 'cancelada', fn ($query) => $query->where('status', StatusSaida::Cancelada))
            ->when(request()->filled('produto_id'), fn ($query) => $query->where('produto_id', request()->input('produto_id')))
            ->when(request()->filled('categoria_id'), fn ($query) => $query->whereHas('produto', fn ($query) => $query->where('categoria_id', request()->input('categoria_id'))))
            ->when(request()->filled('motivo_saida_id'), fn ($query) => $query->where('motivo_saida_id', request()->input('motivo_saida_id')))
            ->when(request()->filled('colaborador_id'), fn ($query) => $query->where('colaborador_id', request()->input('colaborador_id')))
            ->when($podeEscolherCd && request()->filled('cd_id'), fn ($query) => $query->where('cd_id', request()->input('cd_id')))
            ->when(request()->filled('data_inicio'), fn ($query) => $query->whereDate('data', '>=', request()->input('data_inicio')))
            ->when(request()->filled('data_fim'), fn ($query) => $query->whereDate('data', '<=', request()->input('data_fim')))
            ->latest('data')
            ->paginate(20)
            ->withQueryString();

        return [
            'saidas' => $saidas,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
            'categorias' => Categoria::query()->orderBy('nome')->pluck('nome', 'id'),
            'motivos' => MotivoSaida::query()->orderBy('nome')->pluck('nome', 'id'),
            'colaboradores' => ($podeEscolherCd ? Colaborador::withoutGlobalScopes() : Colaborador::query())->orderBy('nome')->pluck('nome', 'id'),
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
            'podeEscolherCd' => $podeEscolherCd,
            'statusOptions' => ['ativa' => 'Ativas', 'cancelada' => 'Canceladas', 'todas' => 'Todas'],
        ];
    }
}
