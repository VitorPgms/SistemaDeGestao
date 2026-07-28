<div>
    <div class="flex justify-end mb-4">
        @can('create', \App\Modules\Estoque\Models\Saida::class)
            <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\NovaSaida::getUrl() }}" wire:navigate>Nova Saída</x-button>
        @endcan
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Estoque\Filament\Pages\Saidas::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
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
                        <th class="px-6 py-3">Retirado por</th>
                        <th class="px-6 py-3">Motivo</th>
                        <th class="px-6 py-3">Data</th>
                        <th class="px-6 py-3 text-right">Qtd.</th>
                        @can('acessar-todos-cds')
                            <th class="px-6 py-3">CD</th>
                        @endcan
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
                            @can('acessar-todos-cds')
                                <td class="px-6 py-3 text-gray-600">{{ $saida->centroDistribuicao->nome }}</td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhuma saída registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $saidas->links() }}
    </div>
</div>
