<div>
    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('estoque.update', $estoque) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="quantidade_minima" required>Quantidade Mínima</x-input-label>
                <x-text-input type="number" min="0" id="quantidade_minima" name="quantidade_minima" :value="old('quantidade_minima', $estoque->quantidade_minima)" />
                <x-input-error name="quantidade_minima" />
            </div>

            <div>
                <x-input-label for="quantidade_ideal" required>Quantidade Ideal</x-input-label>
                <x-text-input type="number" min="0" id="quantidade_ideal" name="quantidade_ideal" :value="old('quantidade_ideal', $estoque->quantidade_ideal)" />
                <x-input-error name="quantidade_ideal" />
            </div>

            <div>
                <x-input-label for="localizacao">Localização</x-input-label>
                <x-text-input id="localizacao" name="localizacao" :value="old('localizacao', $estoque->localizacao)" placeholder="Ex: Prateleira A12" />
                <x-input-error name="localizacao" />
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                <x-button as="a" href="{{ \App\Modules\Estoque\Filament\Pages\EstoqueLista::getUrl() }}" wire:navigate variant="secondary">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-card>
</div>
