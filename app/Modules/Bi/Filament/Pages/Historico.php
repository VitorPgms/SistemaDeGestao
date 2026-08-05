<?php

namespace App\Modules\Bi\Filament\Pages;

use App\Models\User;
use App\Modules\Core\Concerns\ResolvesPeriodo;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Inventario\Models\Inventario;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\Setor;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Page fina: consulta somente leitura ao activity_log já registrado por
 * spatie/laravel-activitylog (LogsActivity nos Models). Não cria tabela,
 * Observer ou mecanismo de auditoria novo — só filtra e apresenta o que já
 * está sendo gravado.
 */
class Historico extends Page
{
    use ResolvesPeriodo;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'BI';

    protected static ?string $navigationLabel = 'Histórico';

    protected static ?string $title = 'Histórico';

    protected string $view = 'filament.pages.historico';

    /**
     * Modelos com cd_id (via BelongsToCd) — usados para restringir o
     * histórico ao CD do registro afetado. Produto e Categoria são
     * cadastros globais (sem cd_id em nenhum lugar do sistema), por isso
     * ficam de fora e continuam visíveis independente do CD selecionado.
     */
    private const MODELOS_COM_CD = [
        Entrada::class,
        Saida::class,
        Colaborador::class,
        Setor::class,
        Fornecedor::class,
        ResponsavelRecebimento::class,
        Inventario::class,
        User::class,
    ];

    private const MODULOS = [
        Produto::class => 'Produto',
        Categoria::class => 'Categoria',
        Fornecedor::class => 'Fornecedor',
        Colaborador::class => 'Colaborador',
        Entrada::class => 'Entrada',
        Saida::class => 'Saída',
        Setor::class => 'Setor',
        CentroDistribuicao::class => 'Centro de Distribuição',
        User::class => 'Usuário',
        ResponsavelRecebimento::class => 'Responsável pelo Recebimento',
        Inventario::class => 'Inventário',
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('historico.view') ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Consulta às alterações já registradas pelo Activity Log do sistema.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $podeEscolherCd = $user->can('acessar-todos-cds');
        // Mesma regra do Dashboard: usuário comum nunca escolhe o CD pela
        // URL — o próprio cd_id dele é forçado no servidor.
        $cdId = $podeEscolherCd ? (request()->integer('cd_id') ?: null) : $user->cd_id;

        [$inicio, $fim] = $this->resolverPeriodo();

        $causerId = request()->integer('usuario_id') ?: null;
        $subjectType = request()->input('modulo');
        $subjectType = array_key_exists($subjectType, self::MODULOS) ? $subjectType : null;
        $acao = request()->input('acao');

        $atividades = Activity::query()
            ->with(['causer', 'subject'])
            ->whereBetween('created_at', [$inicio, $fim])
            ->when($cdId, fn (Builder $q) => $this->filtrarPorCd($q, $cdId))
            ->when($causerId, fn (Builder $q) => $q->where('causer_id', $causerId)->where('causer_type', User::class))
            ->when($subjectType, fn (Builder $q) => $q->where('subject_type', $subjectType))
            ->when($acao === 'cancelado', fn (Builder $q) => $q
                ->where('event', 'updated')
                ->whereIn('subject_type', [Entrada::class, Saida::class])
                ->where('properties->attributes->status', 'cancelada'))
            ->when($acao && $acao !== 'cancelado', fn (Builder $q) => $q->where('event', $acao))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $cds = CentroDistribuicao::query()->orderBy('nome')->pluck('nome', 'id');

        return [
            'podeEscolherCd' => $podeEscolherCd,
            'centrosDistribuicao' => $podeEscolherCd ? $cds : collect(),
            'cds' => $cds,
            'cdSelecionado' => $cdId,
            'periodoSelecionado' => request()->input('periodo', 'este_mes'),
            'dataInicio' => $inicio->format('Y-m-d'),
            'dataFim' => $fim->format('Y-m-d'),
            'usuarios' => User::query()
                ->when($cdId, fn ($q) => $q->where('cd_id', $cdId))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'usuarioSelecionado' => $causerId,
            'modulos' => self::MODULOS,
            'moduloSelecionado' => $subjectType,
            'acoes' => [
                'created' => 'Criado',
                'updated' => 'Alterado',
                'cancelado' => 'Cancelado',
                'deleted' => 'Excluído',
            ],
            'acaoSelecionada' => $acao,
            'atividades' => $atividades,
        ];
    }

    private function filtrarPorCd(Builder $query, int $cdId): Builder
    {
        return $query->where(function (Builder $q) use ($cdId) {
            $q->whereHasMorph('subject', self::MODELOS_COM_CD, fn (Builder $sub) => $sub->where('cd_id', $cdId))
                ->orWhere(fn (Builder $q2) => $q2->where('subject_type', CentroDistribuicao::class)->where('subject_id', $cdId))
                ->orWhereIn('subject_type', [Produto::class, Categoria::class]);
        });
    }

    public function moduloLabel(Activity $activity): string
    {
        return self::MODULOS[$activity->subject_type] ?? class_basename($activity->subject_type ?? '') ?: 'Registro';
    }

    public function registroLabel(Activity $activity): string
    {
        $nome = $this->nomeDoRegistro($activity);

        return $nome ? "{$this->moduloLabel($activity)}: {$nome}" : "{$this->moduloLabel($activity)} #{$activity->subject_id}";
    }

    /**
     * @param  Collection<int, string>  $cds
     */
    public function cdLabel(Activity $activity, $cds): ?string
    {
        if ($activity->subject_type === CentroDistribuicao::class) {
            return $activity->subject?->nome ?? data_get($activity->properties, 'old.nome') ?? '—';
        }

        if (! in_array($activity->subject_type, self::MODELOS_COM_CD, true)) {
            return '—';
        }

        $cdId = $activity->subject?->cd_id ?? data_get($activity->properties, 'attributes.cd_id') ?? data_get($activity->properties, 'old.cd_id');

        return $cdId ? ($cds[$cdId] ?? '—') : '—';
    }

    public function acaoLabel(Activity $activity): string
    {
        if ($this->foiCancelamento($activity)) {
            return 'Cancelado';
        }

        return match ($activity->event) {
            'created' => 'Criado',
            'updated' => 'Alterado',
            'deleted' => 'Excluído',
            default => $activity->event ?? '—',
        };
    }

    public function acaoColor(Activity $activity): string
    {
        if ($this->foiCancelamento($activity)) {
            return 'danger';
        }

        return match ($activity->event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default => 'gray',
        };
    }

    /**
     * @return array<int, array{campo: string, antes: mixed, depois: mixed}>
     */
    public function camposAlterados(Activity $activity): array
    {
        $depois = $activity->properties->get('attributes', []);
        $antes = $activity->properties->get('old', []);

        return collect($depois)
            ->reject(fn ($valor, $campo) => $campo === 'motivo_cancelamento')
            ->map(fn ($valor, $campo) => [
                'campo' => $campo,
                'antes' => data_get($antes, $campo),
                'depois' => $valor,
            ])
            ->values()
            ->all();
    }

    /**
     * Só há "old" sem "attributes" em eventos de exclusão — mostra o
     * retrato do registro no momento em que foi excluído.
     *
     * @return array<string, mixed>
     */
    public function valoresRemovidos(Activity $activity): array
    {
        return $activity->properties->get('old', []);
    }

    public function motivoCancelamento(Activity $activity): ?string
    {
        return data_get($activity->properties, 'attributes.motivo_cancelamento');
    }

    private function nomeDoRegistro(Activity $activity): ?string
    {
        $chave = match ($activity->subject_type) {
            Fornecedor::class => 'razao_social',
            User::class => 'name',
            Entrada::class, Saida::class, Inventario::class => null,
            default => 'nome',
        };

        if ($chave === null) {
            return null;
        }

        if ($activity->subject) {
            return $activity->subject->{$chave} ?? null;
        }

        // Registro já excluído: usa o retrato salvo no próprio log, sem
        // inventar dado que não esteja realmente disponível.
        return data_get($activity->properties, "old.{$chave}");
    }

    private function foiCancelamento(Activity $activity): bool
    {
        return $activity->event === 'updated'
            && in_array($activity->subject_type, [Entrada::class, Saida::class], true)
            && data_get($activity->properties, 'attributes.status') === 'cancelada';
    }
}
