@props([
    'placeholder' => 'Select an option...',
    'label' => '',
])

<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }}>
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <button
        @click="open = !open"
        type="button"
        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-left text-sm shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        <span class="text-gray-500">{{ $placeholder }}</span>
        <span class="float-right">{{ __('&darr;') }}</span>
    </button>
    <div
        x-show="open"
        @click.outside="open = false"
        x-cloak
        class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg"
    >
        {{ $slot }}
    </div>
</div>
