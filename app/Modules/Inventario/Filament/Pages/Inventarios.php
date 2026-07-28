<?php

namespace App\Modules\Inventario\Filament\Pages;

use App\Modules\Inventario\Models\Inventario;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (inventarios.index) dentro
 * do shell nativo do Filament. Regra de negócio continua em
 * InventarioController e InventarioService.
 */
class Inventarios extends Page
{
    protected static ?string $slug = 'inventarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected static ?string $navigationLabel = 'Inventário';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Inventário';

    protected string $view = 'filament.pages.inventarios';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('inventarios.view') ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Contagens físicas de estoque, em andamento e concluídas.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'inventarios' => Inventario::query()
                ->with(['responsavel', 'centroDistribuicao'])
                ->withCount('itens')
                ->latest('data_contagem')
                ->paginate(20),
        ];
    }
}
