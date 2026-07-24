@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'bg-green-50 text-green-800 border-green-200',
        'danger' => 'bg-red-50 text-red-800 border-red-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
    ][$type];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm $styles"]) }}>
    {{ $slot }}
</div>
