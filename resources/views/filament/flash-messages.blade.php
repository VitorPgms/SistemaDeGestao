@if (session('sucesso'))
    <x-alert type="success" class="mb-6">{{ session('sucesso') }}</x-alert>
@endif

@if (session('erro'))
    <x-alert type="danger" class="mb-6">{{ session('erro') }}</x-alert>
@endif
