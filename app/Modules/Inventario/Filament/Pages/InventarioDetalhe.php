<?php

namespace App\Modules\Inventario\Filament\Pages;

use App\Modules\Inventario\Models\Inventario;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Page fina para a contagem física de um inventário. Recebe o {inventario}
 * via route-model binding, igual ao InventarioController::show() de antes.
 * Salvar contagem, finalizar e cancelar continuam em InventarioController.
 */
class InventarioDetalhe extends Page
{
    protected static ?string $slug = 'inventarios/{inventario}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.inventario-detalhe';

    public Inventario $inventario;

    public function mount(Inventario $inventario): void
    {
        abort_unless(auth()->user()?->can('view', $inventario), 403);

        $this->inventario = $inventario;
    }

    public function getTitle(): string
    {
        return "Inventário #{$this->inventario->id}";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Aberto em ' . $this->inventario->data_contagem->format('d/m/Y') . ' por ' . $this->inventario->responsavel->name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'inventario' => $this->inventario,
            'itens' => $this->inventario->itens()->with(['produto', 'produtoVariacao'])->orderBy('produto_id')->get(),
        ];
    }
}
