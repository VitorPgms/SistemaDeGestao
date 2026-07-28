<?php

namespace App\Modules\Estoque\Filament\Pages;

use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Page fina: só entrega a mesma view de sempre (entradas.index) dentro do
 * shell nativo do Filament. Regra de negócio continua em EntradaController
 * (store/update/cancelar) e EstoqueService.
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
        $user = auth()->user();
        $podeEscolherCd = $user->can('acessar-todos-cds');
        $status = request()->input('status', 'ativa');

        $entradas = Entrada::query()
            ->with(['produto', 'produtoVariacao', 'fornecedor', 'responsavelRecebimento', 'canceladoPor', 'centroDistribuicao'])
            ->when($status === 'ativa', fn ($query) => $query->where('status', StatusEntrada::Ativa))
            ->when($status === 'cancelada', fn ($query) => $query->where('status', StatusEntrada::Cancelada))
            ->when(request()->filled('produto_id'), fn ($query) => $query->where('produto_id', request()->input('produto_id')))
            ->when(request()->filled('categoria_id'), fn ($query) => $query->whereHas('produto', fn ($query) => $query->where('categoria_id', request()->input('categoria_id'))))
            ->when(request()->filled('fornecedor_id'), fn ($query) => $query->where('fornecedor_id', request()->input('fornecedor_id')))
            ->when(request()->filled('responsavel_recebimento_id'), fn ($query) => $query->where('responsavel_recebimento_id', request()->input('responsavel_recebimento_id')))
            ->when($podeEscolherCd && request()->filled('cd_id'), fn ($query) => $query->where('cd_id', request()->input('cd_id')))
            ->when(request()->filled('numero_nota_fiscal'), fn ($query) => $query->where('numero_nota_fiscal', 'like', '%'.request()->input('numero_nota_fiscal').'%'))
            ->when(request()->filled('data_inicio'), fn ($query) => $query->whereDate('data_entrega', '>=', request()->input('data_inicio')))
            ->when(request()->filled('data_fim'), fn ($query) => $query->whereDate('data_entrega', '<=', request()->input('data_fim')))
            ->latest('data_entrega')
            ->paginate(20)
            ->withQueryString();

        return [
            'entradas' => $entradas,
            'produtos' => Produto::query()->orderBy('nome')->pluck('nome', 'id'),
            'categorias' => Categoria::query()->orderBy('nome')->pluck('nome', 'id'),
            'fornecedores' => ($podeEscolherCd ? Fornecedor::withoutGlobalScopes() : Fornecedor::query())->orderBy('razao_social')->pluck('razao_social', 'id'),
            'responsaveisRecebimento' => ($podeEscolherCd ? ResponsavelRecebimento::withoutGlobalScopes() : ResponsavelRecebimento::query())->orderBy('nome')->pluck('nome', 'id'),
            'centrosDistribuicao' => $podeEscolherCd ? CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id') : collect(),
            'podeEscolherCd' => $podeEscolherCd,
            'statusOptions' => ['ativa' => 'Ativas', 'cancelada' => 'Canceladas', 'todas' => 'Todas'],
        ];
    }
}
