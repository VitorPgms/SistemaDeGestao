<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Models\User;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Estoque\Support\VariacoesParaFormulario;
use App\Modules\Organizacional\Models\Colaborador;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Page fina para editar uma saída ativa. Recebe a {saida} via
 * route-model binding. A gravação (PUT) continua em SaidaController::update().
 */
class SaidaEditar extends Page
{
    protected static ?string $slug = 'saidas/{saida}/editar';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.saida-editar';

    public Saida $saida;

    public function mount(Saida $saida): void
    {
        abort_unless(auth()->user()?->can('update', $saida), 403);

        $this->saida = $saida->load(['produto', 'produtoVariacao', 'colaborador', 'motivoSaida']);
    }

    public function getTitle(): string
    {
        return 'Editar Saída';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->saida->produto->nome
            .($this->saida->produtoVariacao ? ' ('.$this->saida->produtoVariacao->valor.')' : '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $cdId = $this->saida->cd_id;

        $produtos = Produto::query()->where('status', 'ativo')->with('variacoes')->orderBy('nome')->get();

        return [
            'saida' => $this->saida,
            'produtos' => $produtos,
            'produtosJson' => VariacoesParaFormulario::mapear($produtos),
            'motivos' => MotivoSaida::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id'),
            'colaboradores' => Colaborador::withoutGlobalScopes()->where('cd_id', $cdId)->where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']),
            'usuarios' => User::query()->where('cd_id', $cdId)->where('ativo', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
