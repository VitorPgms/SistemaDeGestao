@php
    $emAndamento = $inventario->status === \App\Modules\Inventario\Enums\StatusInventario::EmAndamento;
@endphp

<x-app-layout title="Inventário #{{ $inventario->id }}">
    <x-page-heading :description="'Aberto em ' . $inventario->data_contagem->format('d/m/Y') . ' por ' . $inventario->responsavel->name">
        Inventário #{{ $inventario->id }}

        <x-slot name="actions">
            <x-badge :color="$inventario->status->getColor()">{{ $inventario->status->getLabel() }}</x-badge>

            @can('update', $inventario)
                @if ($emAndamento)
                    <form method="POST" action="{{ route('inventarios.cancelar', $inventario) }}" onsubmit="return confirm('Cancelar este inventário? Nenhum ajuste será aplicado.');">
                        @csrf
                        <x-button type="submit" variant="secondary">Cancelar Inventário</x-button>
                    </form>
                    <form method="POST" action="{{ route('inventarios.finalizar', $inventario) }}" onsubmit="return confirm('Finalizar o inventário aplica os ajustes de estoque para todos os itens com divergência. Confirma?');">
                        @csrf
                        <x-button type="submit">Finalizar e Ajustar Estoque</x-button>
                    </form>
                @endif
            @endcan
        </x-slot>
    </x-page-heading>

    @if ($inventario->observacoes)
        <x-card class="mb-6">
            <p class="text-sm text-gray-600">{{ $inventario->observacoes }}</p>
        </x-card>
    @endif

    <form method="POST" action="{{ route('inventarios.contagem', $inventario) }}">
        @csrf
        @method('PUT')

        <x-card class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <th class="px-6 py-3">Produto</th>
                            <th class="px-6 py-3 text-right">Sistema</th>
                            <th class="px-6 py-3 text-right">Contado</th>
                            <th class="px-6 py-3 text-right">Divergência</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($itens as $item)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    {{ $item->produto->nome }}
                                    @if ($item->produtoVariacao)
                                        <span class="text-gray-500">({{ $item->produtoVariacao->valor }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ $item->quantidade_sistema }}</td>
                                <td class="px-6 py-3 text-right">
                                    <input
                                        type="number"
                                        min="0"
                                        name="quantidades[{{ $item->id }}]"
                                        value="{{ old('quantidades.'.$item->id, $item->quantidade_contada) }}"
                                        @disabled(! $emAndamento)
                                        class="w-24 rounded-lg border-0 py-1 px-2 text-right text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm disabled:bg-gray-50 disabled:text-gray-500"
                                    />
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if ($item->contado())
                                        @php $divergencia = $item->divergencia(); @endphp
                                        <span class="font-medium {{ $divergencia === 0 ? 'text-gray-500' : ($divergencia > 0 ? 'text-green-600' : 'text-red-600') }}">
                                            {{ $divergencia > 0 ? '+' : '' }}{{ $divergencia }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($emAndamento)
            @can('update', $inventario)
                <div class="flex justify-end mt-6">
                    <x-button type="submit">Salvar Contagem</x-button>
                </div>
            @endcan
        @endif
    </form>
</x-app-layout>
