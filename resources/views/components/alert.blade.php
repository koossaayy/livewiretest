@props([
    'title' => '',
    'description' => '',
    'type' => 'info',
])

@php
    $colors = match ($type) {
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        default => 'bg-blue-50 border-blue-200 text-blue-800',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 {$colors}"]) }}>
    @if ($title)
        <h4 class="text-sm font-semibold mb-1">{{ $title }}</h4>
    @endif
    @if ($description)
        <p class="text-sm">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
