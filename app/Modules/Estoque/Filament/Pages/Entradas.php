<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Produto;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (entradas.index) dentro do
 * shell nativo do Filament. Regra de negócio continua em EntradaController
 * (store) e EstoqueService.
 */
class Entradas extends Page
{
    protected static ?string $slug = 'entradas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected static ?string $navigationLabel = 'Entradas';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Entradas';

    protected string $view = 'filament.pages.entradas';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('entradas.view') ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Histórico de recebimentos de mercadoria.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $entradas = Entrada::query()
            ->with(['produto', 'produtoVariacao', 'fornecedor', 'centroDistribuicao'])
            ->when(request()->filled('produto_id'), fn ($query) => $query->where('produto_id', request()->input('produto_id')))
            ->when(request()->filled('data_inicio'), fn ($query) => $query->whereDate('data_entrega', '>=', request()->input('data_inicio')))
            ->when(request()->filled('data_fim'), fn ($query) => $query->whereDate('data_entrega', '<=', request()->input('data_fim')))
            ->latest('data_entrega')
            ->paginate(20)
            ->withQueryString();

        return [
            'entradas' => $entradas,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
        ];
    }
}
