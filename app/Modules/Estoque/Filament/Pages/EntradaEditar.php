<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Estoque\Support\VariacoesParaFormulario;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Page fina para editar uma entrada ativa. Recebe a {entrada} via
 * route-model binding. A gravação (PUT) continua em EntradaController::update().
 */
class EntradaEditar extends Page
{
    protected static ?string $slug = 'entradas/{entrada}/editar';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.entrada-editar';

    public Entrada $entrada;

    public function mount(Entrada $entrada): void
    {
        abort_unless(auth()->user()?->can('update', $entrada), 403);

        $this->entrada = $entrada->load(['produto', 'produtoVariacao', 'fornecedor', 'responsavelRecebimento']);
    }

    public function getTitle(): string
    {
        return 'Editar Entrada';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->entrada->produto->nome
            .($this->entrada->produtoVariacao ? ' ('.$this->entrada->produtoVariacao->valor.')' : '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $cdId = $this->entrada->cd_id;

        $produtos = Produto::query()->where('status', 'ativo')->with('variacoes')->orderBy('nome')->get();

        return [
            'entrada' => $this->entrada,
            'produtos' => $produtos,
            'produtosJson' => VariacoesParaFormulario::mapear($produtos),
            'fornecedores' => Fornecedor::withoutGlobalScopes()->where('cd_id', $cdId)->where('ativo', true)->orderBy('razao_social')->get(['id', 'razao_social']),
            'responsaveisRecebimento' => ResponsavelRecebimento::withoutGlobalScopes()->where('cd_id', $cdId)->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ];
    }
}
