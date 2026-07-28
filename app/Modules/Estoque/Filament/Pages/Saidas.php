<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (saidas.index) dentro do
 * shell nativo do Filament. Regra de negócio continua em SaidaController
 * (store) e EstoqueService.
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
        $saidas = Saida::query()
            ->with(['produto', 'produtoVariacao', 'colaborador', 'motivoSaida', 'centroDistribuicao'])
            ->when(request()->filled('produto_id'), fn ($query) => $query->where('produto_id', request()->input('produto_id')))
            ->when(request()->filled('data_inicio'), fn ($query) => $query->whereDate('data', '>=', request()->input('data_inicio')))
            ->when(request()->filled('data_fim'), fn ($query) => $query->whereDate('data', '<=', request()->input('data_fim')))
            ->latest('data')
            ->paginate(20)
            ->withQueryString();

        return [
            'saidas' => $saidas,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
        ];
    }
}
