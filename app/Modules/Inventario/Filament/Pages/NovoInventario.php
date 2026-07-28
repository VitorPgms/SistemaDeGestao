<?php

namespace App\Modules\Inventario\Filament\Pages;

use App\Modules\Organizacional\Models\CentroDistribuicao;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Page fina para o formulário de abertura de inventário: mesma view, mesmo
 * POST para InventarioController::store(). Não aparece na navegação — só é
 * alcançada pelo botão "Novo Inventário" na listagem.
 */
class NovoInventario extends Page
{
    protected static ?string $slug = 'inventarios/novo';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Novo Inventário';

    protected string $view = 'filament.pages.novo-inventario';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('inventarios.manage') ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Ao abrir, o sistema tira um retrato da quantidade atual de todos os produtos ativos do CD para comparar com a contagem física.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $podeEscolherCd = auth()->user()->can('acessar-todos-cds');

        return [
            'podeEscolherCd' => $podeEscolherCd,
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
        ];
    }
}
