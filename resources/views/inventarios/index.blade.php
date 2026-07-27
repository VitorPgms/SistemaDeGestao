<x-app-layout title="Inventário">
    <x-page-heading description="Contagens físicas de estoque, em andamento e concluídas.">
        Inventário

        <x-slot name="actions">
            @can('create', \App\Modules\Inventario\Models\Inventario::class)
                <x-button as="a" href="{{ route('inventarios.create') }}">Novo Inventário</x-button>
            @endcan
        </x-slot>
    </x-page-heading>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Data da Contagem</th>
                        @can('acessar-todos-cds')
                            <th class="px-6 py-3">CD</th>
                        @endcan
                        <th class="px-6 py-3">Responsável</th>
                        <th class="px-6 py-3 text-right">Itens</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($inventarios as $inventario)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $inventario->data_contagem->format('d/m/Y') }}</td>
                            @can('acessar-todos-cds')
                                <td class="px-6 py-3 text-gray-600">{{ $inventario->centroDistribuicao->nome }}</td>
                            @endcan
                            <td class="px-6 py-3 text-gray-600">{{ $inventario->responsavel->name }}</td>
                            <td class="px-6 py-3 text-right text-gray-600">{{ $inventario->itens_count }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$inventario->status->getColor()">{{ $inventario->status->getLabel() }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('inventarios.show', $inventario) }}" class="text-gray-500 hover:text-gray-900">Abrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhum inventário registrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $inventarios->links() }}
    </div>
</x-app-layout>
