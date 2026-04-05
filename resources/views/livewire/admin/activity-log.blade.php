<div class="max-w-5xl mx-auto py-8 px-4" x-data="{ showDetails: null }">

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('success') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Activity Log') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Track all actions and events across your store.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                wire:click="exportLog"
                title="{{ __('Export activity log as CSV') }}"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm font-medium"
            >
                {{ __('Export Log') }}
            </button>
            <button
                wire:click="clearLog"
                wire:confirm="{{ __('Are you sure you want to clear the activity log? This action cannot be undone.') }}"
                title="{{ __('Clear all activity entries') }}"
                class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 text-sm font-medium"
            >
                {{ __('Clear Log') }}
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-1">
            <label for="activity-search" class="sr-only">{{ __('Search activities') }}</label>
            <input
                wire:model.live.debounce.300ms="search"
                id="activity-search"
                type="search"
                placeholder="{{ __('Search by description or user...') }}"
                class="w-full rounded-lg border-gray-300 shadow-sm"
            />
        </div>
        <div>
            <label for="type-filter" class="sr-only">{{ __('Filter by type') }}</label>
            <select wire:model.live="typeFilter" id="type-filter" class="rounded-lg border-gray-300 shadow-sm">
                <option value="all">{{ __('All Types') }}</option>
                <option value="order">{{ __('Orders') }}</option>
                <option value="user">{{ __('Users') }}</option>
                <option value="payment">{{ __('Payments') }}</option>
                <option value="system">{{ __('System') }}</option>
            </select>
        </div>
        <div>
            <label for="date-range" class="sr-only">{{ __('Date range') }}</label>
            <select wire:model.live="dateRange" id="date-range" class="rounded-lg border-gray-300 shadow-sm">
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('This Week') }}</option>
                <option value="month">{{ __('This Month') }}</option>
                <option value="all">{{ __('All Time') }}</option>
            </select>
        </div>
    </div>

    {{-- Activity Timeline --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('Recent Activity') }}</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($activities as $activity)
                @php
                    $typeIcons = [
                        'order' => 'text-blue-600 bg-blue-100',
                        'user' => 'text-green-600 bg-green-100',
                        'payment' => 'text-yellow-600 bg-yellow-100',
                        'system' => 'text-gray-600 bg-gray-100',
                    ];
                    $typeLabels = [
                        'order' => __('Order'),
                        'user' => __('User'),
                        'payment' => __('Payment'),
                        'system' => __('System'),
                    ];
                    $iconClass = $typeIcons[$activity->type] ?? 'text-gray-600 bg-gray-100';
                @endphp

                <div
                    wire:key="activity-{{ $activity->id }}"
                    class="px-6 py-4 hover:bg-gray-50 cursor-pointer"
                    @click="showDetails = showDetails === {{ $activity->id }} ? null : {{ $activity->id }}"
                >
                    <div class="flex items-start gap-4">
                        {{-- Type Badge --}}
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full {{ $iconClass }} text-sm font-bold flex-shrink-0" title="{{ __('Activity type: :param_1', ['param_1' => $typeLabels[$activity->type] ?? 'Unknown']) }}">
                            {{ strtoupper(substr($activity->type, 0, 1)) }}
                        </span>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ __('By :param_1 &middot; :param_2', ['param_1' => $activity->user->name, 'param_2' => $activity->created_at->diffForHumans()]) }}
                            </p>
                        </div>

                        {{-- Action Badge --}}
                        <span class="text-xs px-2 py-1 rounded-full font-medium
                            {{ $activity->action === 'error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}
                        ">
                            {{ ucfirst($activity->action) }}
                        </span>

                        {{-- Expand Icon --}}
                        <span class="text-gray-400 text-sm" x-text="showDetails === {{ $activity->id }} ? '▲' : '▼'"></span>
                    </div>

                    {{-- Expandable Details --}}
                    <div
                        x-show="showDetails === {{ $activity->id }}"
                        x-cloak
                        x-transition
                        class="mt-3 ml-14 text-xs text-gray-500 bg-gray-50 rounded-lg p-3"
                    >
                        <p><span class="font-medium text-gray-700">{{ __('IP Address:') }}</span> {{ $activity->metadata->ip }}</p>
                        <p class="mt-1"><span class="font-medium text-gray-700">{{ __('Browser:') }}</span> {{ $activity->metadata->browser }}</p>
                        <p class="mt-1"><span class="font-medium text-gray-700">{{ __('Email:') }}</span> {{ $activity->user->email }}</p>
                        <p class="mt-1"><span class="font-medium text-gray-700">{{ __('Timestamp:') }}</span> {{ $activity->created_at->format('M d, Y \a\t g:i:s A') }}</p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500 text-lg font-medium">{{ __('No activity found') }}</p>
                    <p class="text-gray-400 text-sm mt-1">{{ __('There are no events matching your current filters.') }}</p>
                </div>
            @endforelse
        </div>

        @if ($activities->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <p class="text-xs text-gray-400 mt-4 text-center">
        {{ __('Activity logs are retained for 90 days. Older entries are automatically archived.') }}
    </p>
</div>
