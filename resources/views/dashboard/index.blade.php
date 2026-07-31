<div x-data="{ periodo: @js($periodoSelecionado) }">
    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Bi\Filament\Pages\Dashboard::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
            <div>
                <x-input-label for="periodo">Período</x-input-label>
                <x-select-input
                    id="periodo"
                    name="periodo"
                    x-model="periodo"
                    :options="[
                        'hoje' => 'Hoje',
                        '7dias' => 'Últimos 7 dias',
                        '30dias' => 'Últimos 30 dias',
                        'este_mes' => 'Este mês',
                        'mes_anterior' => 'Mês anterior',
                        'personalizado' => 'Período personalizado',
                    ]"
                    :selected="$periodoSelecionado"
                />
            </div>
            <div x-show="periodo === 'personalizado'" x-cloak>
                <x-input-label for="data_inicio">De</x-input-label>
                <x-text-input type="date" id="data_inicio" name="data_inicio" value="{{ $dataInicio }}" />
            </div>
            <div x-show="periodo === 'personalizado'" x-cloak>
                <x-input-label for="data_fim">Até</x-input-label>
                <x-text-input type="date" id="data_fim" name="data_fim" value="{{ $dataFim }}" />
            </div>
            <div>
                <x-input-label for="categoria_id">Categoria</x-input-label>
                <x-select-input id="categoria_id" name="categoria_id" :options="$categorias" placeholder="Todas" :selected="$categoriaSelecionada" />
            </div>
            <div>
                <x-input-label for="fornecedor_id">Fornecedor</x-input-label>
                <x-select-input id="fornecedor_id" name="fornecedor_id" :options="$fornecedores" placeholder="Todos" :selected="$fornecedorSelecionado" />
            </div>
            <div>
                <x-input-label for="produto_id">Produto</x-input-label>
                <x-select-input id="produto_id" name="produto_id" :options="$produtos" placeholder="Todos" :selected="$produtoSelecionado" />
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
        <x-card>
            <p class="text-sm text-gray-500">Produtos cadastrados</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalProdutosCadastrados }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Itens em estoque</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $quantidadeTotalEstoque }}</p>
        </x-card>
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
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Saídas no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $saidasQuantidade }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Valor comprado no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">R$ {{ number_format($entradasValor, 2, ',', '.') }}</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-card class="lg:col-span-2">
            <h2 class="text-sm font-medium text-gray-700 mb-4">Entradas x Saídas por dia</h2>
            <canvas id="grafico-movimentacoes" height="100"></canvas>
        </x-card>

        <x-card>
            <h2 class="text-sm font-medium text-gray-700 mb-4">Situação do Estoque</h2>
            <canvas id="grafico-situacao" height="180"></canvas>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-card class="lg:col-span-2 !p-0">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-sm font-medium text-gray-700">Produtos que precisam de atenção</h2>
                <a href="{{ \App\Modules\Estoque\Filament\Pages\EstoqueLista::getUrl() }}" wire:navigate class="text-sm font-medium text-gray-900 hover:underline">Ver estoque</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <th class="px-6 py-3">Produto</th>
                            <th class="px-6 py-3 text-right">Atual</th>
                            <th class="px-6 py-3 text-right">Mínimo</th>
                            <th class="px-6 py-3 text-right">Ideal</th>
                            <th class="px-6 py-3">Situação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($alertasEstoque as $estoque)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    {{ $estoque->produto->nome }}
                                    @if ($estoque->produtoVariacao)
                                        <span class="text-gray-500">({{ $estoque->produtoVariacao->valor }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-gray-900">{{ $estoque->quantidade_atual }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ $estoque->quantidade_minima }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ $estoque->quantidade_ideal }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$estoque->situacao()->getColor()">{{ $estoque->situacao()->getLabel() }}</x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum item crítico ou em atenção.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

    <x-card class="mb-6 !p-0">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-medium text-gray-700">Produtos mais movimentados (Top 5 saídas no período)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Produto</th>
                        <th class="px-6 py-3">Categoria</th>
                        <th class="px-6 py-3 text-right">Quantidade retirada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($maisMovimentados as $item)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $item['produto'] }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item['categoria'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $item['quantidade'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">Nenhuma saída no período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card class="!p-0">
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
        const canvasMovimentacoes = document.getElementById('grafico-movimentacoes');
        Chart.getChart(canvasMovimentacoes)?.destroy();

        new Chart(canvasMovimentacoes, {
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

        const canvasSituacao = document.getElementById('grafico-situacao');
        Chart.getChart(canvasSituacao)?.destroy();

        new Chart(canvasSituacao, {
            type: 'doughnut',
            data: {
                labels: ['Normal', 'Atenção', 'Crítico'],
                datasets: [{
                    data: [@json($itensNormais), @json($itensAtencao), @json($itensCriticos)],
                    backgroundColor: ['#16a34a', '#d97706', '#dc2626'],
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    </script>
    @endscript
</div>
