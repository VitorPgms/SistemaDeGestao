<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Produto;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (estoque.index) dentro do
 * shell nativo do Filament. Nome "EstoqueLista" (não "Estoque") para não
 * colidir com App\Modules\Estoque\Models\Estoque.
 */
class EstoqueLista extends Page
{
    protected static ?string $slug = 'estoque';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected static ?string $navigationLabel = 'Estoque';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Estoque';

    protected string $view = 'filament.pages.estoque-lista';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('estoque.view') ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Situação atual do estoque por produto e Centro de Distribuição.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $estoques = Estoque::query()
            ->with(['produto', 'produtoVariacao', 'centroDistribuicao', 'fornecedorPreferencial'])
            ->when(request()->filled('produto_id'), fn ($query) => $query->where('produto_id', request()->input('produto_id')))
            ->orderBy('produto_id')
            ->paginate(20)
            ->withQueryString();

        return [
            'estoques' => $estoques,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
        ];
    }
}
