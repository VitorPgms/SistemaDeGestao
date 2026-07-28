<div>
    <div class="flex justify-end mb-4">
        @can('create', \App\Modules\Estoque\Models\Entrada::class)
            <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\NovaEntrada::getUrl() }}" wire:navigate>Nova Entrada</x-button>
        @endcan
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Estoque\Filament\Pages\Entradas::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label for="produto_id">Produto</x-input-label>
                <x-select-input id="produto_id" name="produto_id" :options="$produtos" placeholder="Todos" :selected="request('produto_id')" />
            </div>
            <div>
                <x-input-label for="data_inicio">De</x-input-label>
                <x-text-input type="date" id="data_inicio" name="data_inicio" :value="request('data_inicio')" />
            </div>
            <div>
                <x-input-label for="data_fim">Até</x-input-label>
                <x-text-input type="date" id="data_fim" name="data_fim" :value="request('data_fim')" />
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
                        <th class="px-6 py-3">Fornecedor</th>
                        <th class="px-6 py-3">NF</th>
                        <th class="px-6 py-3">Entrega</th>
                        <th class="px-6 py-3 text-right">Qtd.</th>
                        <th class="px-6 py-3 text-right">Valor Total</th>
                        @can('acessar-todos-cds')
                            <th class="px-6 py-3">CD</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entradas as $entrada)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                {{ $entrada->produto->nome }}
                                @if ($entrada->produtoVariacao)
                                    <span class="text-gray-500">({{ $entrada->produtoVariacao->valor }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->fornecedor->razao_social }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->numero_nota_fiscal }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->data_entrega->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $entrada->quantidade }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">R$ {{ number_format($entrada->valor_total, 2, ',', '.') }}</td>
                            @can('acessar-todos-cds')
                                <td class="px-6 py-3 text-gray-600">{{ $entrada->centroDistribuicao->nome }}</td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">Nenhuma entrada registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $entradas->links() }}
    </div>
</div>
