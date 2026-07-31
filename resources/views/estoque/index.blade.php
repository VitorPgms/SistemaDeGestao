<div>
    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Estoque\Filament\Pages\EstoqueLista::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label for="produto_id">Produto</x-input-label>
                <x-select-input id="produto_id" name="produto_id" :options="$produtos" placeholder="Todos" :selected="request('produto_id')" />
            </div>
            <div>
                <x-input-label for="data_inicio">Período - De</x-input-label>
                <x-text-input type="date" id="data_inicio" name="data_inicio" :value="$dataInicio" />
            </div>
            <div>
                <x-input-label for="data_fim">Período - Até</x-input-label>
                <x-text-input type="date" id="data_fim" name="data_fim" :value="$dataFim" />
            </div>
            <div>
                <x-button type="submit" variant="secondary" class="w-full">Filtrar</x-button>
            </div>
        </form>
    </x-card>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Produto</th>
                        @can('acessar-todos-cds')
                            <th class="px-6 py-3">CD</th>
                        @endcan
                        <th class="px-6 py-3 text-right">Atual</th>
                        <th class="px-6 py-3 text-right">Mínimo</th>
                        <th class="px-6 py-3 text-right">Ideal</th>
                        <th class="px-6 py-3 text-right">Comprado no Período</th>
                        <th class="px-6 py-3 text-right">Saída no Período</th>
                        <th class="px-6 py-3">Última Entrada</th>
                        <th class="px-6 py-3">Última Saída</th>
                        <th class="px-6 py-3">Fornecedor</th>
                        <th class="px-6 py-3">Situação</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($estoques as $estoque)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                {{ $estoque->produto->nome }}
                                @if ($estoque->produtoVariacao)
                                    <span class="text-gray-500">({{ $estoque->produtoVariacao->valor }})</span>
                                @endif
                            </td>
                            @can('acessar-todos-cds')
                                <td class="px-6 py-3 text-gray-600">{{ $estoque->centroDistribuicao->nome }}</td>
                            @endcan
                            <td class="px-6 py-3 text-right text-gray-900">{{ $estoque->quantidade_atual }}</td>
                            <td class="px-6 py-3 text-right text-gray-600">{{ $estoque->quantidade_minima }}</td>
                            <td class="px-6 py-3 text-right text-gray-600">{{ $estoque->quantidade_ideal }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $estoque->total_entradas_periodo }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $estoque->total_saidas_periodo }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $estoque->ultima_entrada_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $estoque->ultima_saida_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $estoque->fornecedorPreferencial?->razao_social ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$estoque->situacao()->getColor()">{{ $estoque->situacao()->getLabel() }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                @can('update', $estoque)
                                    <a href="{{ \App\Modules\Estoque\Filament\Pages\EstoqueEditar::getUrl(['estoque' => $estoque]) }}" wire:navigate class="text-gray-500 hover:text-gray-900">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-8 text-center text-gray-500">Nenhum estoque registrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $estoques->links() }}
    </div>
</div>
