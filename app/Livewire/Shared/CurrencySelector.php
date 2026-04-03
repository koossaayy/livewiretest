<?php

namespace App\Livewire\Shared;

use Livewire\Component;

class CurrencySelector extends Component
{
    public string $label = '';
    public string $default = 'USD';
    public string $description = '';
    public string $selected = '';

    public function mount(): void
    {
        $this->selected = $this->default;
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            @if ($label)
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
            @endif
            <select wire:model="selected" class="w-full rounded-lg border-gray-300 shadow-sm">
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
                <option value="CAD">CAD - Canadian Dollar</option>
            </select>
            @if ($description)
                <p class="mt-1 text-xs text-gray-500">{{ $description }}</p>
            @endif
        </div>
        HTML;
    }
}
