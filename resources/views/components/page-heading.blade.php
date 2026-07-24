@props(['description' => null])

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $slot }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
