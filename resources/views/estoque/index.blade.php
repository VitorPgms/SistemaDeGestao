<x-app-layout title="Estoque">
    <x-page-heading description="Situação atual do estoque por produto e Centro de Distribuição.">
        Estoque
    </x-page-heading>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('estoque.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label for="produto_id">Produto</x-input-label>
                <x-select-input id="produto_id" name="produto_id" :options="$produtos" placeholder="Todos" :selected="request('produto_id')" />
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
                            <td class="px-6 py-3 text-gray-600">{{ $estoque->ultima_entrada_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $estoque->ultima_saida_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $estoque->fornecedorPreferencial?->razao_social ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$estoque->situacao()->getColor()">{{ $estoque->situacao()->getLabel() }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                @can('update', $estoque)
                                    <a href="{{ route('estoque.edit', $estoque) }}" class="text-gray-500 hover:text-gray-900">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">Nenhum estoque registrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $estoques->links() }}
    </div>
</x-app-layout>
