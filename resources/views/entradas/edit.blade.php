<div>
    <x-card>
        <form method="POST" action="{{ route('entradas.update', $entrada) }}" id="form-entrada" class="space-y-6">
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
                        :selected="old('produto_id', $entrada->produto_id)"
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
                    <x-select-input
                        id="fornecedor_id"
                        name="fornecedor_id"
                        placeholder="Selecione o fornecedor"
                        :options="$fornecedores->pluck('razao_social', 'id')"
                        :selected="old('fornecedor_id', $entrada->fornecedor_id)"
                    />
                    <x-input-error name="fornecedor_id" />
                </div>

                <div>
                    <x-input-label for="responsavel_recebimento_id" required>Responsável pelo recebimento</x-input-label>
                    <x-select-input
                        id="responsavel_recebimento_id"
                        name="responsavel_recebimento_id"
                        placeholder="Selecione o responsável"
                        :options="$responsaveisRecebimento->pluck('nome', 'id')"
                        :selected="old('responsavel_recebimento_id', $entrada->responsavel_recebimento_id)"
                    />
                    <x-input-error name="responsavel_recebimento_id" />
                </div>

                <div>
                    <x-input-label for="numero_nota_fiscal" required>Número da Nota Fiscal</x-input-label>
                    <x-text-input id="numero_nota_fiscal" name="numero_nota_fiscal" :value="old('numero_nota_fiscal', $entrada->numero_nota_fiscal)" />
                    <x-input-error name="numero_nota_fiscal" />
                </div>

                <div>
                    <x-input-label for="data_compra" required>Data da Compra</x-input-label>
                    <x-text-input type="date" id="data_compra" name="data_compra" :value="old('data_compra', $entrada->data_compra->toDateString())" />
                    <x-input-error name="data_compra" />
                </div>

                <div>
                    <x-input-label for="data_entrega" required>Data da Entrega</x-input-label>
                    <x-text-input type="date" id="data_entrega" name="data_entrega" :value="old('data_entrega', $entrada->data_entrega->toDateString())" />
                    <x-input-error name="data_entrega" />
                </div>

                <div>
                    <x-input-label for="quantidade" required>Quantidade</x-input-label>
                    <x-text-input type="number" min="1" id="quantidade" name="quantidade" :value="old('quantidade', $entrada->quantidade)" />
                    <x-input-error name="quantidade" />
                </div>

                <div>
                    <x-input-label for="valor_unitario" required>Valor Unitário (R$)</x-input-label>
                    <x-text-input type="number" min="0" step="0.01" id="valor_unitario" name="valor_unitario" :value="old('valor_unitario', $entrada->valor_unitario)" />
                    <x-input-error name="valor_unitario" />
                </div>
            </div>

            <div>
                <x-input-label for="observacoes">Observações</x-input-label>
                <textarea name="observacoes" id="observacoes" rows="3" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">{{ old('observacoes', $entrada->observacoes) }}</textarea>
                <x-input-error name="observacoes" />
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\Entradas::getUrl() }}" wire:navigate variant="secondary">Cancelar</x-button>
                <x-button type="submit">Salvar Alterações</x-button>
            </div>
        </form>
    </x-card>

    @script
    <script>
        (function () {
            const produtos = @json($produtosJson);
            const variacaoAtualId = @json(old('produto_variacao_id', $entrada->produto_variacao_id));

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
