<div>
    <div class="flex justify-end mb-4">
        @can('create', \App\Modules\Estoque\Models\Entrada::class)
            <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\NovaEntrada::getUrl() }}" wire:navigate>Nova Entrada</x-button>
        @endcan
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Estoque\Filament\Pages\Entradas::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div class="sm:col-span-2">
                <x-input-label for="busca">Busca</x-input-label>
                <x-text-input id="busca" name="busca" placeholder="Produto ou nº da NF" :value="request('busca')" />
            </div>
            <div>
                <x-input-label for="produto_id">Produto</x-input-label>
                <x-select-input id="produto_id" name="produto_id" :options="$produtos" placeholder="Todos" :selected="request('produto_id')" />
            </div>
            <div>
                <x-input-label for="categoria_id">Categoria</x-input-label>
                <x-select-input id="categoria_id" name="categoria_id" :options="$categorias" placeholder="Todas" :selected="request('categoria_id')" />
            </div>
            <div>
                <x-input-label for="fornecedor_id">Fornecedor</x-input-label>
                <x-select-input id="fornecedor_id" name="fornecedor_id" :options="$fornecedores" placeholder="Todos" :selected="request('fornecedor_id')" />
            </div>
            @if ($podeEscolherCd)
                <div>
                    <x-input-label for="cd_id">Centro de Distribuição</x-input-label>
                    <x-select-input id="cd_id" name="cd_id" :options="$centrosDistribuicao" placeholder="Todos" :selected="request('cd_id')" />
                </div>
            @endif
            <div>
                <x-input-label for="numero_nota_fiscal">Número da NF</x-input-label>
                <x-text-input id="numero_nota_fiscal" name="numero_nota_fiscal" :value="request('numero_nota_fiscal')" />
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
                        <th class="px-6 py-3">Status</th>
                        @can('acessar-todos-cds')
                            <th class="px-6 py-3">CD</th>
                        @endcan
                        <th class="px-6 py-3">Ações</th>
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
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->fornecedor?->razao_social }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->numero_nota_fiscal }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->data_entrega->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $entrada->quantidade }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">R$ {{ number_format($entrada->valor_total, 2, ',', '.') }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$entrada->status->getColor()">{{ $entrada->status->getLabel() }}</x-badge>
                                @if ($entrada->status === \App\Modules\Estoque\Enums\StatusEntrada::Cancelada)
                                    <div class="text-xs text-gray-500 mt-1">{{ $entrada->motivo_cancelamento }}</div>
                                @endif
                            </td>
                            @can('acessar-todos-cds')
                                <td class="px-6 py-3 text-gray-600">{{ $entrada->centroDistribuicao->nome }}</td>
                            @endcan
                            <td class="px-6 py-3">
                                @if ($entrada->status === \App\Modules\Estoque\Enums\StatusEntrada::Ativa)
                                    @can('update', $entrada)
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ \App\Modules\Estoque\Filament\Pages\EntradaEditar::getUrl(['entrada' => $entrada]) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:underline">Editar</a>

                                            <form method="POST" action="{{ route('entradas.cancelar', $entrada) }}" class="flex items-center gap-2" onsubmit="return confirm('Cancelar esta entrada? A quantidade será removida do estoque.');">
                                                @csrf
                                                <input type="text" name="motivo_cancelamento" required placeholder="Motivo do cancelamento" class="block w-40 rounded-lg border-0 py-1 px-2 text-xs text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900" />
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Cancelar</button>
                                            </form>
                                        </div>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">Nenhuma entrada registrada ainda.</td>
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
