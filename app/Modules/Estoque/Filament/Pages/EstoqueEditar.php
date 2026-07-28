<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Models\Estoque;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Page fina para editar os parâmetros de um item de estoque. Recebe o
 * {estoque} via route-model binding, igual ao EstoqueController::edit()
 * de antes. A gravação (PUT) continua em EstoqueController::update().
 */
class EstoqueEditar extends Page
{
    protected static ?string $slug = 'estoque/{estoque}/editar';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.estoque-editar';

    public Estoque $estoque;

    public function mount(Estoque $estoque): void
    {
        abort_unless(auth()->user()?->can('update', $estoque), 403);

        $this->estoque = $estoque->load(['produto', 'produtoVariacao']);
    }

    public function getTitle(): string
    {
        return 'Parâmetros de Estoque';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->estoque->produto->nome
            . ($this->estoque->produtoVariacao ? ' (' . $this->estoque->produtoVariacao->valor . ')' : '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['estoque' => $this->estoque];
    }
}
