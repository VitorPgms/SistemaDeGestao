<div>
    <x-card>
        <form method="POST" action="{{ route('saidas.update', $saida) }}" id="form-saida" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="produto_id" required>Produto</x-input-label>
                    <x-select-input
                        id="produto_id"
                        name="produto_id"
                        placeholder="Selecione o produto"
                        :options="$produtos->pluck('nome', 'id')"
                        :selected="old('produto_id', $saida->produto_id)"
                    />
                    <x-input-error name="produto_id" />
                </div>

                <div id="bloco-variacao" class="hidden">
                    <x-input-label for="produto_variacao_id" required>Variação</x-input-label>
                    <x-select-input id="produto_variacao_id" name="produto_variacao_id" placeholder="Selecione a variação" />
                    <x-input-error name="produto_variacao_id" />
                </div>

                <div>
                    <x-input-label for="quantidade" required>Quantidade</x-input-label>
                    <x-text-input type="number" min="1" id="quantidade" name="quantidade" :value="old('quantidade', $saida->quantidade)" />
                    <x-input-error name="quantidade" />
                </div>

                <div>
                    <x-input-label for="motivo_saida_id" required>Motivo</x-input-label>
                    <x-select-input id="motivo_saida_id" name="motivo_saida_id" :options="$motivos" placeholder="Selecione o motivo" :selected="old('motivo_saida_id', $saida->motivo_saida_id)" />
                    <x-input-error name="motivo_saida_id" />
                </div>

                <div>
                    <x-input-label for="colaborador_id" required>Quem retirou</x-input-label>
                    <select name="colaborador_id" id="colaborador_id" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">
                        <option value="">Selecione o colaborador</option>
                        @foreach ($colaboradores as $colaborador)
                            <option value="{{ $colaborador->id }}" @selected(old('colaborador_id', $saida->colaborador_id) == $colaborador->id)>{{ $colaborador->nome }}</option>
                        @endforeach
                    </select>
                    <x-input-error name="colaborador_id" />
                </div>

                <div>
                    <x-input-label for="liberado_por" required>Quem liberou</x-input-label>
                    <select name="liberado_por" id="liberado_por" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">
                        <option value="">Selecione o usuário</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected(old('liberado_por', $saida->liberado_por) == $usuario->id)>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error name="liberado_por" />
                </div>

                <div>
                    <x-input-label for="data" required>Data</x-input-label>
                    <x-text-input type="date" id="data" name="data" :value="old('data', $saida->data->toDateString())" />
                    <x-input-error name="data" />
                </div>

                <div>
                    <x-input-label for="hora" required>Hora</x-input-label>
                    <x-text-input type="time" id="hora" name="hora" :value="old('hora', \Illuminate\Support\Carbon::parse($saida->hora)->format('H:i'))" />
                    <x-input-error name="hora" />
                </div>
            </div>

            <div>
                <x-input-label for="observacoes">Observações</x-input-label>
                <textarea name="observacoes" id="observacoes" rows="3" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">{{ old('observacoes', $saida->observacoes) }}</textarea>
                <x-input-error name="observacoes" />
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\Saidas::getUrl() }}" wire:navigate variant="secondary">Cancelar</x-button>
                <x-button type="submit">Salvar Alterações</x-button>
            </div>
        </form>
    </x-card>

    @script
    <script>
        (function () {
            const produtos = @json($produtosJson);
            const variacaoAtualId = @json(old('produto_variacao_id', $saida->produto_variacao_id));

            const produtoSelect = document.getElementById('produto_id');
            const blocoVariacao = document.getElementById('bloco-variacao');
            const variacaoSelect = document.getElementById('produto_variacao_id');

            function atualizarVariacoes() {
                const produto = produtos[produtoSelect.value];
                variacaoSelect.innerHTML = '<option value="">Selecione a variação</option>';

                if (!produto || !produto.temVariacao) {
                    blocoVariacao.classList.add('hidden');
                    return;
                }

                produto.variacoes.forEach(function (variacao) {
                    const option = document.createElement('option');
                    option.value = variacao.id;
                    option.textContent = variacao.valor;
                    if (variacaoAtualId && String(variacaoAtualId) === String(variacao.id)) {
                        option.selected = true;
                    }
                    variacaoSelect.appendChild(option);
                });

                blocoVariacao.classList.remove('hidden');
            }

            produtoSelect.addEventListener('change', atualizarVariacoes);
            atualizarVariacoes();
        })();
    </script>
    @endscript
</div>
