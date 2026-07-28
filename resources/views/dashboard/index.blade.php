<div>
    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Bi\Filament\Pages\Dashboard::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <x-input-label for="data_inicio">Data início</x-input-label>
                <x-text-input type="date" id="data_inicio" name="data_inicio" value="{{ $dataInicio }}" />
            </div>
            <div>
                <x-input-label for="data_fim">Data fim</x-input-label>
                <x-text-input type="date" id="data_fim" name="data_fim" value="{{ $dataFim }}" />
            </div>
            @if ($podeEscolherCd)
                <div>
                    <x-input-label for="cd_id">Centro de Distribuição</x-input-label>
                    <x-select-input id="cd_id" name="cd_id" :options="$centrosDistribuicao" placeholder="Todos" :selected="$cdSelecionado" />
                </div>
            @endif
            <div>
                <x-button type="submit" variant="secondary" class="w-full">Filtrar</x-button>
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="{{ \App\Modules\Estoque\Filament\Pages\EstoqueLista::getUrl() }}" wire:navigate>
            <x-card>
                <p class="text-sm text-gray-500">Itens em situação Crítica</p>
                <p class="mt-2 text-3xl font-semibold text-red-600">{{ $itensCriticos }}</p>
            </x-card>
        </a>
        <a href="{{ \App\Modules\Estoque\Filament\Pages\EstoqueLista::getUrl() }}" wire:navigate>
            <x-card>
                <p class="text-sm text-gray-500">Itens em Atenção</p>
                <p class="mt-2 text-3xl font-semibold text-amber-600">{{ $itensAtencao }}</p>
            </x-card>
        </a>
        <x-card>
            <p class="text-sm text-gray-500">Entradas no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $entradasQuantidade }}</p>
            <p class="text-sm text-gray-500">R$ {{ number_format($entradasValor, 2, ',', '.') }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Saídas no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $saidasQuantidade }}</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="lg:col-span-2">
            <h2 class="text-sm font-medium text-gray-700 mb-4">Entradas x Saídas por dia</h2>
            <canvas id="grafico-movimentacoes" height="100"></canvas>
        </x-card>

        <x-card>
            <h2 class="text-sm font-medium text-gray-700 mb-4">Estoque mínimo — não lidas</h2>
            @forelse ($notificacoes as $notificacao)
                <div class="py-3 border-b border-gray-100 last:border-0">
                    <p class="text-sm text-gray-900">{{ $notificacao->data['mensagem'] }}</p>
                    <form method="POST" action="{{ route('dashboard.notificacoes.marcar-lida', $notificacao->id) }}" class="mt-1">
                        @csrf
                        <button type="submit" class="text-xs text-gray-500 hover:text-gray-900">Marcar como lida</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nenhuma notificação pendente.</p>
            @endforelse
        </x-card>
    </div>

    <x-card class="mt-6 !p-0">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-medium text-gray-700">Últimas movimentações</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Tipo</th>
                        <th class="px-6 py-3">Produto</th>
                        <th class="px-6 py-3 text-right">Quantidade</th>
                        <th class="px-6 py-3">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ultimasMovimentacoes as $movimentacao)
                        <tr>
                            <td class="px-6 py-3">
                                <x-badge :color="$movimentacao['tipo'] === 'Entrada' ? 'success' : 'info'">{{ $movimentacao['tipo'] }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-gray-900">{{ $movimentacao['produto'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-600">{{ $movimentacao['quantidade'] }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $movimentacao['data']->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">Nenhuma movimentação registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @script
    <script>
        const canvas = document.getElementById('grafico-movimentacoes');
        const chartExistente = Chart.getChart(canvas);
        chartExistente?.destroy();

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: @json($graficoLabels),
                datasets: [
                    {
                        label: 'Entradas',
                        data: @json($graficoEntradas),
                        backgroundColor: '#16a34a',
                    },
                    {
                        label: 'Saídas',
                        data: @json($graficoSaidas),
                        backgroundColor: '#2563eb',
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    </script>
    @endscript
</div>
