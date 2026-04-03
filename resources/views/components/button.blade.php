@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'bg-blue-600 text-white hover:bg-blue-700',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium transition-colors {$classes}"]) }}
>
    {{ $slot }}
</button>
