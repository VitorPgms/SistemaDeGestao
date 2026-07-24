<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sistema de Gestão' }}</title>
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex h-16 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a href="{{ url('/admin') }}" class="font-semibold text-lg text-gray-900">Sistema de Gestão</a>
                    <nav class="hidden md:flex gap-1 text-sm font-medium">
                        <a href="{{ route('entradas.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('entradas.*') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Entradas</a>
                        <a href="{{ route('saidas.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('saidas.*') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Saídas</a>
                        <a href="{{ route('estoque.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('estoque.*') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Estoque</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    @if (auth()->user()->can('acessar-todos-cds'))
                        <span class="px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">Todos os CDs</span>
                    @else
                        <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">{{ auth()->user()->centroDistribuicao->nome }}</span>
                    @endif
                    <span class="text-gray-600">{{ auth()->user()->name }}</span>
                    <a href="{{ url('/admin') }}" class="text-gray-500 hover:text-gray-900">Painel</a>
                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-gray-900">Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                @if (session('sucesso'))
                    <x-alert type="success" class="mb-6">{{ session('sucesso') }}</x-alert>
                @endif

                @if (session('erro'))
                    <x-alert type="danger" class="mb-6">{{ session('erro') }}</x-alert>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
