@props([
    'label' => '',
    'description' => '',
    'type' => 'text',
])

<div>
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500']) }}
    />
    @if ($description)
        <p class="mt-1 text-xs text-gray-500">{{ $description }}</p>
    @endif
</div>
