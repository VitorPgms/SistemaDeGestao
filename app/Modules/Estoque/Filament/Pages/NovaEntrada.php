<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Estoque\Support\VariacoesParaFormulario;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Filament\Pages\Page;

/**
 * Page fina para o formulário de nova entrada: mesma view, mesmo POST para
 * EntradaController::store(). Não aparece na navegação — só é alcançada
 * pelo botão "Nova Entrada" na listagem.
 */
class NovaEntrada extends Page
{
    protected static ?string $slug = 'entradas/nova';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Nova Entrada';

    protected string $view = 'filament.pages.nova-entrada';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('entradas.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $podeEscolherCd = $user->can('acessar-todos-cds');

        $produtos = Produto::query()->where('status', 'ativo')->with('variacoes')->orderBy('nome')->get();

        return [
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
            'podeEscolherCd' => $podeEscolherCd,
            'produtos' => $produtos,
            'produtosJson' => VariacoesParaFormulario::mapear($produtos),
            'fornecedores' => ($podeEscolherCd ? Fornecedor::withoutGlobalScopes() : Fornecedor::query())
                ->where('ativo', true)
                ->orderBy('razao_social')
                ->get(['id', 'razao_social', 'cd_id']),
            'responsaveisRecebimento' => ($podeEscolherCd ? ResponsavelRecebimento::withoutGlobalScopes() : ResponsavelRecebimento::query())
                ->where('ativo', true)
                ->orderBy('nome')
                ->get(['id', 'nome', 'cd_id']),
        ];
    }
}
