<x-app-layout title="Novo Inventário">
    <x-page-heading description="Ao abrir, o sistema tira um retrato da quantidade atual de todos os produtos ativos do CD para comparar com a contagem física.">
        Novo Inventário
    </x-page-heading>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('inventarios.store') }}" class="space-y-6">
            @csrf

            @if ($podeEscolherCd)
                <div>
                    <x-input-label for="cd_id" required>Centro de Distribuição</x-input-label>
                    <x-select-input id="cd_id" name="cd_id" :options="$centrosDistribuicao" placeholder="Selecione o CD" />
                    <x-input-error name="cd_id" />
                </div>
            @endif

            <div>
                <x-input-label for="data_contagem" required>Data da Contagem</x-input-label>
                <x-text-input type="date" id="data_contagem" name="data_contagem" :value="old('data_contagem', now()->toDateString())" />
                <x-input-error name="data_contagem" />
            </div>

            <div>
                <x-input-label for="observacoes">Observações</x-input-label>
                <textarea name="observacoes" id="observacoes" rows="3" class="block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-gray-900 sm:text-sm">{{ old('observacoes') }}</textarea>
                <x-input-error name="observacoes" />
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <x-button as="a" href="{{ route('inventarios.index') }}" variant="secondary">Cancelar</x-button>
                <x-button type="submit">Abrir Inventário</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
