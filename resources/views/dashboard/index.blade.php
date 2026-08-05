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
            <p class="text-sm text-gray-500">Colaboradores ativos</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalColaboradores }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Centros de Distribuição</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalCds }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Exame periódico mais próximo</p>
            @if ($proximoExamePeriodico)
                @php
                    $statusExame = $proximoExamePeriodico->statusExamePeriodico();
                    $corStatusExame = match ($statusExame) {
                        'vencido' => 'danger',
                        'proximo' => 'warning',
                        default => 'success',
                    };
                    $labelStatusExame = match ($statusExame) {
                        'vencido' => 'Vencido',
                        'proximo' => 'Próximo',
                        default => 'Normal',
                    };
                @endphp
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $proximoExamePeriodico->nome }}</p>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $proximoExamePeriodico->data_proximo_exame_periodico->format('d/m/Y') }}
                    <x-badge :color="$corStatusExame">{{ $labelStatusExame }}</x-badge>
                </p>
            @else
                <p class="mt-2 text-sm text-gray-500">Nenhum exame cadastrado</p>
            @endif
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
            <p class="mt-1 text-xs text-gray-500">{{ $entradasRegistros }} registros no período</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Produtos recebidos no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $produtosRecebidos }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Saídas no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $saidasQuantidade }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $saidasRegistros }} registros no período</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Valor comprado no período</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">R$ {{ number_format($entradasValor, 2, ',', '.') }}</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-card>
            <h2 class="text-sm font-medium text-gray-700 mb-4">Alertas</h2>
            <ul class="divide-y divide-gray-100">
                <li class="py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-700">Itens em estoque crítico</span>
                    <x-badge color="danger">{{ $itensCriticos }}</x-badge>
                </li>
                <li class="py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-700">Itens em atenção (abaixo do ideal)</span>
                    <x-badge color="warning">{{ $itensAtencao }}</x-badge>
                </li>
                <li class="py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-700">Entradas previstas em breve</span>
                    <x-badge color="info">{{ $proximasEntradas->count() }}</x-badge>
                </li>
                <li class="py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-700">Exames periódicos vencidos ou próximos</span>
                    <x-badge color="warning">{{ $colaboradoresExameAlerta }}</x-badge>
                </li>
            </ul>
        </x-card>

        <x-card>
            <h2 class="text-sm font-medium text-gray-700 mb-4">Compras e consumo no período</h2>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Comprado</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $entradasQuantidade }} un.</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Retirado</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $saidasQuantidade }} un.</span>
                </div>
                <div class="flex items-center justify-between py-3 border-t border-gray-100">
                    <span class="text-sm text-gray-500">Saldo de movimentação</span>
                    <span class="text-lg font-semibold {{ $saldoMovimentacao >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $saldoMovimentacao >= 0 ? '+' : '' }}{{ $saldoMovimentacao }} un.
                    </span>
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="mb-6 !p-0">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-medium text-gray-700">Exames periódicos mais próximos</h2>
            @if ($colaboradoresExameAlerta > 0)
                <a href="{{ $urlExamesPeriodicos }}" class="text-sm font-medium text-gray-900 hover:underline">Ver mais</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Colaborador</th>
                        @if ($podeEscolherCd)
                            <th class="px-6 py-3">CD</th>
                        @endif
                        <th class="px-6 py-3">Último exame</th>
                        <th class="px-6 py-3">Próximo exame</th>
                        <th class="px-6 py-3">Situação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($examesPeriodicosTop5 as $colaborador)
                        @php $statusExame = $colaborador->statusExamePeriodico(); @endphp
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $colaborador->nome }}</td>
                            @if ($podeEscolherCd)
                                <td class="px-6 py-3 text-gray-600">{{ $colaborador->centroDistribuicao->nome }}</td>
                            @endif
                            <td class="px-6 py-3 text-gray-600">{{ $colaborador->data_ultimo_exame_periodico?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $colaborador->data_proximo_exame_periodico->format('d/m/Y') }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$statusExame === 'vencido' ? 'danger' : 'warning'">
                                    {{ $statusExame === 'vencido' ? 'Vencido' : 'Próximo do vencimento' }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $podeEscolherCd ? 5 : 4 }}" class="px-6 py-8 text-center text-gray-500">Nenhum exame periódico vencido ou próximo do vencimento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

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
                            @if ($podeEscolherCd)
                                <th class="px-6 py-3">CD</th>
                            @endif
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
                                @if ($podeEscolherCd)
                                    <td class="px-6 py-3 text-gray-600">{{ $estoque->centroDistribuicao->nome }}</td>
                                @endif
                                <td class="px-6 py-3 text-right text-gray-900">{{ $estoque->quantidade_atual }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ $estoque->quantidade_minima }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ $estoque->quantidade_ideal }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$estoque->situacao()->getColor()">{{ $estoque->situacao()->getLabel() }}</x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $podeEscolherCd ? 6 : 5 }}" class="px-6 py-8 text-center text-gray-500">Nenhum item crítico ou em atenção.</td>
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

    <x-card class="mb-6 !p-0">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-medium text-gray-700">Próximas entradas previstas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Produto</th>
                        <th class="px-6 py-3">Fornecedor</th>
                        <th class="px-6 py-3 text-right">Quantidade</th>
                        <th class="px-6 py-3">Previsão de entrega</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($proximasEntradas as $entrada)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                {{ $entrada->produto->nome }}
                                @if ($entrada->produtoVariacao)
                                    <span class="text-gray-500">({{ $entrada->produtoVariacao->valor }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->fornecedor?->razao_social ?? '—' }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $entrada->quantidade }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->data_entrega->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">Nenhuma entrada prevista.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card class="mb-6 !p-0">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-medium text-gray-700">Entradas recentes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Produto</th>
                        <th class="px-6 py-3">Fornecedor</th>
                        <th class="px-6 py-3">NF</th>
                        <th class="px-6 py-3 text-right">Quantidade</th>
                        <th class="px-6 py-3">Data</th>
                        @if ($podeEscolherCd)
                            <th class="px-6 py-3">CD</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entradasRecentes as $entrada)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                {{ $entrada->produto->nome }}
                                @if ($entrada->produtoVariacao)
                                    <span class="text-gray-500">({{ $entrada->produtoVariacao->valor }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->fornecedor?->razao_social ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $entrada->numero_nota_fiscal ?? '—' }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $entrada->quantidade }}</td>
                            <td class="px-6 py-3 text-gray-600">
                                {{ $entrada->data_entrega->format('d/m/Y') }}
                                @if ($entrada->data_entrega->isFuture())
                                    <x-badge color="warning">Prevista</x-badge>
                                @endif
                            </td>
                            @if ($podeEscolherCd)
                                <td class="px-6 py-3 text-gray-600">{{ $entrada->centroDistribuicao->nome }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $podeEscolherCd ? 6 : 5 }}" class="px-6 py-8 text-center text-gray-500">Nenhuma entrada registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card class="!p-0">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-medium text-gray-700">Saídas recentes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Produto</th>
                        <th class="px-6 py-3">Colaborador</th>
                        <th class="px-6 py-3 text-right">Quantidade</th>
                        <th class="px-6 py-3">Data</th>
                        @if ($podeEscolherCd)
                            <th class="px-6 py-3">CD</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($saidasRecentes as $saida)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">
                                {{ $saida->produto->nome }}
                                @if ($saida->produtoVariacao)
                                    <span class="text-gray-500">({{ $saida->produtoVariacao->valor }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $saida->colaborador?->nome ?? '—' }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ $saida->quantidade }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $saida->data->format('d/m/Y') }}</td>
                            @if ($podeEscolherCd)
                                <td class="px-6 py-3 text-gray-600">{{ $saida->centroDistribuicao->nome }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $podeEscolherCd ? 5 : 4 }}" class="px-6 py-8 text-center text-gray-500">Nenhuma saída registrada ainda.</td>
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
