@php
    $temFiltrosAvancados = request()->filled('categoria_id')
        || request()->filled('motivo_saida_id')
        || request()->filled('colaborador_id')
        || ($podeEscolherCd && request()->filled('cd_id'))
        || (request()->filled('status') && request('status') !== 'ativa');

    $totalColunas = 6 + ($podeEscolherCd ? 1 : 0);
@endphp

{{-- CSS escrito à mão (não classes utilitárias do Tailwind) só para o modal
de cancelamento: o projeto não tem Node/npm disponível para rodar o build
do Vite, então qualquer classe Tailwind nova fica ausente do CSS compilado
até um rebuild acontecer em outra máquina. O resto da tela usa componentes
Blade já existentes (x-button, x-input-label, x-card...), cujas classes já
estão no bundle atual, então continuam normalmente. --}}
<style>
    .cancelamento-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(17, 24, 39, .5);
        padding: 1rem;
    }

    .cancelamento-modal-panel {
        width: 100%;
        max-width: 32rem;
        border-radius: .75rem;
        background-color: #fff;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1);
    }

    .cancelamento-modal-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .cancelamento-modal-title {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .cancelamento-modal-text {
        font-size: .875rem;
        color: #4b5563;
        margin: 0;
    }

    .cancelamento-modal-textarea {
        display: block;
        box-sizing: border-box;
        width: 100%;
        border: 0;
        border-radius: .5rem;
        padding: .375rem .75rem;
        font-size: .875rem;
        color: #111827;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, .05);
        outline: 1px solid #d1d5db;
        outline-offset: -1px;
    }

    .cancelamento-modal-textarea:focus {
        outline: 2px solid #111827;
        outline-offset: -2px;
    }

    .cancelamento-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
        padding-top: .5rem;
    }

    .cancelamento-modal-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border: none;
        border-radius: .5rem;
        padding: .5rem 1rem;
        font-size: .875rem;
        font-weight: 600;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, .05);
        cursor: pointer;
    }

    .cancelamento-modal-btn-primary {
        background-color: #111827;
        color: #fff;
    }

    .cancelamento-modal-btn-primary:hover:not(:disabled) {
        background-color: #374151;
    }

    .cancelamento-modal-btn-primary:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .cancelamento-modal-btn-danger {
        background-color: #dc2626;
        color: #fff;
    }

    .cancelamento-modal-btn-danger:hover {
        background-color: #ef4444;
    }
</style>

<div
    x-data="{
        filtrosAvancadosAbertos: @js($temFiltrosAvancados),
        cancelamento: { aberto: false, etapa: 1, motivo: '', acaoUrl: '', produtoNome: '' },
        abrirCancelamento(url, produto) {
            this.cancelamento = { aberto: true, etapa: 1, motivo: '', acaoUrl: url, produtoNome: produto };
        },
        fecharCancelamento() {
            this.cancelamento.aberto = false;
        },
    }"
>
    <div class="flex justify-end mb-4">
        @can('create', \App\Modules\Estoque\Models\Saida::class)
            <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\NovaSaida::getUrl() }}" wire:navigate>Nova Saída</x-button>
        @endcan
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Estoque\Filament\Pages\Saidas::getUrl() }}">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <x-input-label for="produto_id">Produto</x-input-label>
                    <x-select-input id="produto_id" name="produto_id" :options="$produtos" placeholder="Todos" :selected="request('produto_id')" />
                </div>
                <div>
                    <x-input-label for="data_inicio">Período - De</x-input-label>
                    <x-text-input type="date" id="data_inicio" name="data_inicio" :value="request('data_inicio')" />
                </div>
                <div>
                    <x-input-label for="data_fim">Período - Até</x-input-label>
                    <x-text-input type="date" id="data_fim" name="data_fim" :value="request('data_fim')" />
                </div>
            </div>

            <div x-show="filtrosAvancadosAbertos" x-cloak class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end mt-4 pt-4 border-t border-gray-200">
                <div>
                    <x-input-label for="categoria_id">Categoria</x-input-label>
                    <x-select-input id="categoria_id" name="categoria_id" :options="$categorias" placeholder="Todas" :selected="request('categoria_id')" />
                </div>
                <div>
                    <x-input-label for="motivo_saida_id">Motivo</x-input-label>
                    <x-select-input id="motivo_saida_id" name="motivo_saida_id" :options="$motivos" placeholder="Todos" :selected="request('motivo_saida_id')" />
                </div>
                <div>
                    <x-input-label for="colaborador_id">Retirado por</x-input-label>
                    <x-select-input id="colaborador_id" name="colaborador_id" :options="$colaboradores" placeholder="Todos" :selected="request('colaborador_id')" />
                </div>
                <div>
                    <x-input-label for="status">Status</x-input-label>
                    <x-select-input id="status" name="status" :options="$statusOptions" :selected="request('status', 'ativa')" />
                </div>
                @if ($podeEscolherCd)
                    <div>
                        <x-input-label for="cd_id">Centro de Distribuição</x-input-label>
                        <x-select-input id="cd_id" name="cd_id" :options="$centrosDistribuicao" placeholder="Todos" :selected="request('cd_id')" />
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4 mt-4">
                <button type="button" @click="filtrosAvancadosAbertos = !filtrosAvancadosAbertos" class="text-sm font-medium text-gray-900 hover:underline">
                    <span x-show="!filtrosAvancadosAbertos">Mais filtros</span>
                    <span x-show="filtrosAvancadosAbertos" x-cloak>Menos filtros</span>
                </button>
                <x-button type="submit" variant="secondary">Filtrar</x-button>
            </div>
        </form>
    </x-card>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Produto</th>
                        <th class="px-6 py-3">Retirado por</th>
                        <th class="px-6 py-3">Motivo</th>
                        <th class="px-6 py-3">Data</th>
                        <th class="px-6 py-3 text-right">Qtd.</th>
                        <th class="px-6 py-3">Status</th>
                        @can('acessar-todos-cds')
                            <th class="px-6 py-3">CD</th>
                        @endcan
                        <th class="px-6 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($saidas as $saida)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                {{ $saida->produto->nome }}
                                @if ($saida->produtoVariacao)
                                    <span class="text-gray-500">({{ $saida->produtoVariacao->valor }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $saida->colaborador->nome }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $saida->motivoSaida->nome }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $saida->data->format('d/m/Y') }} {{ \Illuminate\Support\Carbon::parse($saida->hora)->format('H:i') }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $saida->quantidade }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$saida->status->getColor()">{{ $saida->status->getLabel() }}</x-badge>
                                @if ($saida->status === \App\Modules\Estoque\Enums\StatusSaida::Cancelada)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $saida->motivo_cancelamento }}<br>
                                        Por {{ $saida->canceladoPor?->name }} em {{ $saida->cancelado_em?->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            @can('acessar-todos-cds')
                                <td class="px-6 py-3 text-gray-600">{{ $saida->centroDistribuicao->nome }}</td>
                            @endcan
                            <td class="px-6 py-3">
                                @if ($saida->status === \App\Modules\Estoque\Enums\StatusSaida::Ativa)
                                    @can('update', $saida)
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ \App\Modules\Estoque\Filament\Pages\SaidaEditar::getUrl(['saida' => $saida]) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:underline">Editar</a>

                                            <button
                                                type="button"
                                                @click="abrirCancelamento(@js(route('saidas.cancelar', $saida)), @js($saida->produto->nome))"
                                                class="text-sm font-medium text-red-600 hover:underline text-left"
                                            >Cancelar</button>
                                        </div>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $totalColunas }}" class="px-6 py-8 text-center text-gray-500">Nenhuma saída encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $saidas->links() }}
    </div>

    {{-- Cancelamento em duas etapas: motivo obrigatório e confirmação final,
    mesmo padrão já usado em Entradas (ver DECISIONS.md).

    x-teleport="body" evita que o overlay fique preso dentro de `.fi-layout`
    (o wrapper do Filament tem overflow-x-clip, que atrapalha position:fixed).
    Teleportar move o nó para o fim do <body> mantendo o mesmo escopo Alpine
    (cancelamento.*) — só muda onde ele fica no DOM. --}}
    <template x-teleport="body">
        <div
            x-show="cancelamento.aberto"
            x-cloak
            class="cancelamento-modal-overlay"
        >
            <div class="cancelamento-modal-panel" @click.outside="fecharCancelamento()">
                <template x-if="cancelamento.etapa === 1">
                    <div class="cancelamento-modal-body">
                        <h3 class="cancelamento-modal-title">Cancelar saída</h3>
                        <p class="cancelamento-modal-text">Informe o motivo do cancelamento de <strong x-text="cancelamento.produtoNome"></strong>.</p>
                        <div>
                            <x-input-label for="motivo-cancelamento">Motivo do cancelamento</x-input-label>
                            <textarea id="motivo-cancelamento" x-model="cancelamento.motivo" rows="3" class="cancelamento-modal-textarea"></textarea>
                        </div>
                        <div class="cancelamento-modal-actions">
                            <x-button type="button" variant="secondary" @click="fecharCancelamento()">Fechar</x-button>
                            <button
                                type="button"
                                :disabled="cancelamento.motivo.trim().length === 0"
                                @click="cancelamento.etapa = 2"
                                class="cancelamento-modal-btn cancelamento-modal-btn-primary"
                            >Confirmar</button>
                        </div>
                    </div>
                </template>

                <template x-if="cancelamento.etapa === 2">
                    <div class="cancelamento-modal-body">
                        <h3 class="cancelamento-modal-title">Tem certeza que deseja cancelar esta saída?</h3>
                        <p class="cancelamento-modal-text">
                            Esta operação devolverá automaticamente esta quantidade ao estoque.<br>
                            O histórico da movimentação será preservado para auditoria.
                        </p>
                        <form method="POST" :action="cancelamento.acaoUrl">
                            @csrf
                            <input type="hidden" name="motivo_cancelamento" :value="cancelamento.motivo" />
                            <div class="cancelamento-modal-actions">
                                <x-button type="button" variant="secondary" @click="cancelamento.etapa = 1">Não, voltar</x-button>
                                <button type="submit" class="cancelamento-modal-btn cancelamento-modal-btn-danger">Sim, cancelar saída</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
