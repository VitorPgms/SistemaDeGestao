<div>
    <x-card>
        <form method="POST" action="{{ route('entradas.store') }}" id="form-entrada" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @if ($podeEscolherCd)
                    <div>
                        <x-input-label for="cd_id" required>Centro de Distribuição</x-input-label>
                        <x-select-input id="cd_id" name="cd_id" :options="$centrosDistribuicao" placeholder="Selecione o CD" />
                        <x-input-error name="cd_id" />
                    </div>
                @endif

                <div>
                    <x-input-label for="produto_id" required>Produto</x-input-label>
                    <x-select-input
                        id="produto_id"
                        name="produto_id"
                        placeholder="Selecione o produto"
                        :options="$produtos->pluck('nome', 'id')"
                    />
                    <x-input-error name="produto_id" />
                </div>

                <div id="bloco-variacao" class="hidden">
                    <x-input-label for="produto_variacao_id" required>Variação</x-input-label>
                    <x-select-input id="produto_variacao_id" name="produto_variacao_id" placeholder="Selecione a variação" />
                    <x-input-error name="produto_variacao_id" />
                </div>

                <div>
                    <x-input-label for="fornecedor_id" required>Fornecedor</x-input-label>
                    <select name="fornecedor_id" id="fornecedor_id" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">
                        <option value="">Selecione o fornecedor</option>
                        @foreach ($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}" data-cd="{{ $fornecedor->cd_id }}" @selected(old('fornecedor_id') == $fornecedor->id)>{{ $fornecedor->razao_social }}</option>
                        @endforeach
                    </select>
                    <x-input-error name="fornecedor_id" />
                </div>

                <div>
                    <x-input-label for="colaborador_recebimento_id" required>Responsável pelo recebimento</x-input-label>
                    <select name="colaborador_recebimento_id" id="colaborador_recebimento_id" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">
                        <option value="">Selecione o colaborador</option>
                        @foreach ($colaboradores as $colaborador)
                            <option value="{{ $colaborador->id }}" data-cd="{{ $colaborador->cd_id }}" @selected(old('colaborador_recebimento_id') == $colaborador->id)>{{ $colaborador->nome }}</option>
                        @endforeach
                    </select>
                    <x-input-error name="colaborador_recebimento_id" />
                </div>

                <div>
                    <x-input-label for="numero_nota_fiscal" required>Número da Nota Fiscal</x-input-label>
                    <x-text-input id="numero_nota_fiscal" name="numero_nota_fiscal" :value="old('numero_nota_fiscal')" />
                    <x-input-error name="numero_nota_fiscal" />
                </div>

                <div>
                    <x-input-label for="data_compra" required>Data da Compra</x-input-label>
                    <x-text-input type="date" id="data_compra" name="data_compra" :value="old('data_compra')" />
                    <x-input-error name="data_compra" />
                </div>

                <div>
                    <x-input-label for="data_entrega" required>Data da Entrega</x-input-label>
                    <x-text-input type="date" id="data_entrega" name="data_entrega" :value="old('data_entrega')" />
                    <x-input-error name="data_entrega" />
                </div>

                <div>
                    <x-input-label for="quantidade" required>Quantidade</x-input-label>
                    <x-text-input type="number" min="1" id="quantidade" name="quantidade" :value="old('quantidade')" />
                    <x-input-error name="quantidade" />
                </div>

                <div>
                    <x-input-label for="valor_unitario" required>Valor Unitário (R$)</x-input-label>
                    <x-text-input type="number" min="0" step="0.01" id="valor_unitario" name="valor_unitario" :value="old('valor_unitario')" />
                    <x-input-error name="valor_unitario" />
                </div>
            </div>

            <div>
                <x-input-label for="observacoes">Observações</x-input-label>
                <textarea name="observacoes" id="observacoes" rows="3" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">{{ old('observacoes') }}</textarea>
                <x-input-error name="observacoes" />
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\Entradas::getUrl() }}" wire:navigate variant="secondary">Cancelar</x-button>
                <x-button type="submit">Registrar Entrada</x-button>
            </div>
        </form>
    </x-card>

    @script
    <script>
        (function () {
            const produtos = @json($produtosJson);

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
                    variacaoSelect.appendChild(option);
                });

                blocoVariacao.classList.remove('hidden');
            }

            produtoSelect.addEventListener('change', atualizarVariacoes);
            atualizarVariacoes();

            @if ($podeEscolherCd)
                const cdSelect = document.getElementById('cd_id');

                function filtrarPorCd(selectId) {
                    const select = document.getElementById(selectId);
                    const cdSelecionado = cdSelect.value;

                    Array.from(select.options).forEach(function (option) {
                        if (!option.value) {
                            return;
                        }
                        option.hidden = cdSelecionado !== '' && option.dataset.cd !== cdSelecionado;
                    });

                    if (select.selectedOptions[0]?.hidden) {
                        select.value = '';
                    }
                }

                cdSelect.addEventListener('change', function () {
                    filtrarPorCd('fornecedor_id');
                    filtrarPorCd('colaborador_recebimento_id');
                });

                filtrarPorCd('fornecedor_id');
                filtrarPorCd('colaborador_recebimento_id');
            @endif
        })();
    </script>
    @endscript
</div>
