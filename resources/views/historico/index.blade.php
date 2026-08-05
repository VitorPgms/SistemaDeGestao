<div x-data="{ periodo: @js($periodoSelecionado) }">
    <x-card class="mb-6">
        <form method="GET" action="{{ \App\Modules\Bi\Filament\Pages\Historico::getUrl() }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
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
                <x-input-label for="usuario_id">Usuário</x-input-label>
                <x-select-input id="usuario_id" name="usuario_id" :options="$usuarios" placeholder="Todos" :selected="$usuarioSelecionado" />
            </div>
            <div>
                <x-input-label for="modulo">Módulo</x-input-label>
                <x-select-input id="modulo" name="modulo" :options="$modulos" placeholder="Todos" :selected="$moduloSelecionado" />
            </div>
            <div>
                <x-input-label for="acao">Ação</x-input-label>
                <x-select-input id="acao" name="acao" :options="$acoes" placeholder="Todas" :selected="$acaoSelecionada" />
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

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm" x-data="{ aberto: null }">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Data/Hora</th>
                        <th class="px-6 py-3">Usuário</th>
                        <th class="px-6 py-3">CD</th>
                        <th class="px-6 py-3">Ação</th>
                        <th class="px-6 py-3">Registro</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($atividades as $atividade)
                        <tr>
                            <td class="px-6 py-3 text-gray-600">{{ $atividade->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3 text-gray-900">{{ $atividade->causer?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $this->cdLabel($atividade, $cds) }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$this->acaoColor($atividade)">{{ $this->acaoLabel($atividade) }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-gray-900">{{ $this->registroLabel($atividade) }}</td>
                            <td class="px-6 py-3 text-right">
                                <button
                                    type="button"
                                    @click="aberto = aberto === {{ $atividade->id }} ? null : {{ $atividade->id }}"
                                    class="text-sm font-medium text-gray-900 hover:underline"
                                >
                                    <span x-text="aberto === {{ $atividade->id }} ? 'Ocultar' : 'Ver detalhes'"></span>
                                </button>
                            </td>
                        </tr>
                        <tr x-show="aberto === {{ $atividade->id }}" x-cloak>
                            <td colspan="6" class="px-6 py-4 bg-gray-50">
                                <div class="text-xs text-gray-500 mb-2">
                                    {{ $atividade->causer?->name ?? 'Sistema' }} — {{ $atividade->created_at->format('d/m/Y H:i') }} — {{ $this->registroLabel($atividade) }}
                                </div>

                                @if ($this->motivoCancelamento($atividade))
                                    <p class="text-sm text-gray-900 mb-2">
                                        <span class="font-medium">Motivo do cancelamento:</span> {{ $this->motivoCancelamento($atividade) }}
                                    </p>
                                @endif

                                @if ($atividade->event === 'deleted')
                                    @php $removidos = $this->valoresRemovidos($atividade); @endphp
                                    @if (empty($removidos))
                                        <p class="text-sm text-gray-500">Sem detalhes adicionais registrados.</p>
                                    @else
                                        <table class="text-sm">
                                            <tbody>
                                                @foreach ($removidos as $campo => $valor)
                                                    <tr>
                                                        <td class="px-6 py-1 font-medium text-gray-700">{{ $campo }}</td>
                                                        <td class="py-1 text-gray-600">{{ is_scalar($valor) ? $valor : json_encode($valor) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @else
                                    @php $campos = $this->camposAlterados($atividade); @endphp
                                    @if (empty($campos))
                                        <p class="text-sm text-gray-500">Sem detalhes adicionais registrados.</p>
                                    @else
                                        <table class="text-sm">
                                            <thead>
                                                <tr class="text-left text-xs text-gray-500 uppercase">
                                                    <th class="px-6 py-1">Campo</th>
                                                    <th class="px-6 py-1">Antes</th>
                                                    <th class="py-1">Depois</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($campos as $campo)
                                                    <tr>
                                                        <td class="px-6 py-1 font-medium text-gray-700">{{ $campo['campo'] }}</td>
                                                        <td class="px-6 py-1 text-gray-600">{{ is_scalar($campo['antes']) ? ($campo['antes'] ?? '—') : json_encode($campo['antes']) }}</td>
                                                        <td class="py-1 text-gray-900">{{ is_scalar($campo['depois']) ? ($campo['depois'] ?? '—') : json_encode($campo['depois']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhuma alteração registrada para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $atividades->links() }}
    </div>
</div>
