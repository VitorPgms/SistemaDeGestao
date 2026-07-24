@props(['name', 'type' => 'text'])

@php
    $hasError = $errors->has($name);
@endphp

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->except('id')->merge([
        'class' => 'block w-full rounded-lg border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset '
            . ($hasError ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300 focus:ring-gray-900')
            . ' placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm',
    ]) }}
/>
