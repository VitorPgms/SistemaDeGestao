<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Models\User;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Support\VariacoesParaFormulario;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Filament\Pages\Page;

/**
 * Page fina para o formulário de nova saída: mesma view, mesmo POST para
 * SaidaController::store(). Não aparece na navegação — só é alcançada pelo
 * botão "Nova Saída" na listagem.
 */
class NovaSaida extends Page
{
    protected static ?string $slug = 'saidas/nova';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Nova Saída';

    protected string $view = 'filament.pages.nova-saida';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('saidas.manage') ?? false;
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
            'colaboradores' => ($podeEscolherCd ? Colaborador::withoutGlobalScopes() : Colaborador::query())
                ->where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cd_id']),
            'usuarios' => User::query()
                ->where('ativo', true)
                ->when(! $podeEscolherCd, fn ($query) => $query->where('cd_id', $user->cd_id))
                ->orderBy('name')
                ->get(['id', 'name', 'cd_id']),
            'motivos' => MotivoSaida::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id'),
        ];
    }
}
