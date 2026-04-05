<div
    class="max-w-7xl mx-auto py-8 px-4"
    x-data="{
        showRefundModal: false,
        showExportModal: false,
        selectedOrder: null,
        refundReason: '',
        bulkSelected: [],
        viewMode: 'table',
        exportCount: 0,
        itemToRemove: '',
    }"
    x-on:order-saved.window="$dispatch('notify', { message: '{{ __('Order saved successfully') }}', title: '{{ __('Success') }}' })"
    x-on:item-removed.window="$dispatch('toast', { message: '{{ __('Item removed from cart') }}', description: '{{ __('You can undo this action') }}' })"
>
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Order Management') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Review, fulfill, and manage customer orders across all channels.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                @click="exportCount = bulkSelected.length || {{ $totalOrders }}; showExportModal = true"
                title="{{ __('Download orders as CSV') }}"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm font-medium"
            >
                {{ __('Export Orders') }}
            </button>
            <button
                wire:click="syncInventory"
                title="{{ __('Sync stock levels with warehouse') }}"
                aria-label="{{ __('Synchronize inventory') }}"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium"
            >
                {{ __('Sync Inventory') }}
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ __('Total Orders') }}</span>
            <p class="text-2xl font-bold">{{ $totalOrders }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ __('Awaiting Fulfillment') }}</span>
            <p class="text-2xl font-bold text-yellow-600">{{ $awaitingCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ __('Shipped Today') }}</span>
            <p class="text-2xl font-bold text-blue-600">{{ $shippedTodayCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ __('Revenue This Month') }}</span>
            <p class="text-2xl font-bold text-green-600">${{ number_format($monthlyRevenue, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ __('Refund Requests') }}</span>
            <p class="text-2xl font-bold text-red-600">{{ $refundRequestCount }}</p>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label for="order-search" class="sr-only">{{ __('Search orders') }}</label>
                <input
                    wire:model.live.debounce.300ms="search"
                    id="order-search"
                    type="search"
                    placeholder="{{ __('Search by order number, customer name, or email...') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm"
                />
            </div>
            <div>
                <label for="status-filter" class="sr-only">{{ __('Filter by status') }}</label>
                <select wire:model.live="statusFilter" id="status-filter" class="rounded-lg border-gray-300 shadow-sm">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending Payment') }}</option>
                    <option value="processing">{{ __('Processing') }}</option>
                    <option value="shipped">{{ __('Shipped') }}</option>
                    <option value="delivered">{{ __('Delivered') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                    <option value="refunded">{{ __('Refunded') }}</option>
                </select>
            </div>
            <div>
                <label for="channel-filter" class="sr-only">{{ __('Filter by sales channel') }}</label>
                <select wire:model.live="channelFilter" id="channel-filter" class="rounded-lg border-gray-300 shadow-sm">
                    <option value="all">{{ __('All Channels') }}</option>
                    <option value="web">{{ __('Website') }}</option>
                    <option value="mobile">{{ __('Mobile App') }}</option>
                    <option value="pos">{{ __('In-Store POS') }}</option>
                    <option value="marketplace">{{ __('Marketplace') }}</option>
                </select>
            </div>
            <div>
                <x-input
                    type="date"
                    wire:model.live="dateFrom"
                    :placeholder="__('Start date')"
                    aria-label="{{ __('Filter from date') }}"
                />
            </div>
            <div>
                <x-input
                    type="date"
                    wire:model.live="dateTo"
                    :placeholder="__('End date')"
                    aria-label="{{ __('Filter to date') }}"
                />
            </div>
        </div>

        {{-- Active Filter Tags --}}
        @if ($statusFilter !== 'all' || $channelFilter !== 'all' || $search)
            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                <span class="text-xs text-gray-500">{{ __('Active filters:') }}</span>
                @if ($search)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                        {{ __('Search:') }} {{ $search }}
                        <button wire:click="$set('search', '')" class="hover:text-blue-900">&times;</button>
                    </span>
                @endif
                <button wire:click="clearFilters" class="text-xs text-red-500 hover:underline ml-auto">
                    {{ __('Clear all filters') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Bulk Actions --}}
    <div x-show="bulkSelected.length > 0" x-cloak class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-4 flex items-center justify-between">
        <span class="text-sm text-indigo-700" x-text="`${bulkSelected.length} {{ __('orders selected') }}`"></span>
        <div class="flex items-center gap-2">
            <button
                wire:click="bulkMarkShipped"
                class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700"
                title="{{ __('Mark selected orders as shipped') }}"
            >
                {{ __('Mark as Shipped') }}
            </button>
            <button
                wire:click="bulkPrintLabels"
                class="text-sm bg-gray-600 text-white px-3 py-1.5 rounded-lg hover:bg-gray-700"
            >
                {{ __('Print Shipping Labels') }}
            </button>
            <button
                @click="if (confirm(`{{ __('Export') }} ${bulkSelected.length} {{ __('selected orders to CSV?') }}`)) { $wire.exportSelected(bulkSelected) }"
                class="text-sm bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700"
            >
                {{ __('Export Selected') }}
            </button>
            <button
                @click="bulkSelected = []"
                class="text-sm text-gray-500 hover:text-gray-700"
            >
                {{ __('Deselect All') }}
            </button>
        </div>
    </div>

    @php
        $statusBadges = [
            'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'label' => __('Pending Payment')],
            'processing' => ['class' => 'bg-blue-100 text-blue-800', 'label' => __('Processing')],
            'shipped' => ['class' => 'bg-indigo-100 text-indigo-800', 'label' => __('Shipped')],
            'delivered' => ['class' => 'bg-green-100 text-green-800', 'label' => __('Delivered')],
            'cancelled' => ['class' => 'bg-gray-100 text-gray-800', 'label' => __('Cancelled')],
            'refunded' => ['class' => 'bg-red-100 text-red-800', 'label' => __('Refunded')],
        ];

        $priorityLabels = [
            'express' => __('Express Shipping'),
            'standard' => __('Standard Delivery'),
            'pickup' => __('Store Pickup'),
        ];
    @endphp

    {{-- Orders Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" class="rounded border-gray-300" aria-label="{{ __('Select all orders') }}" />
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('order_number')" class="hover:text-gray-900 flex items-center gap-1">
                            {{ __('Order Number') }}
                            @if ($sortField === 'order_number')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Customer') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Items') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('total')" class="hover:text-gray-900 flex items-center gap-1">
                            {{ __('Total') }}
                            @if ($sortField === 'total')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Shipping') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('created_at')" class="hover:text-gray-900 flex items-center gap-1">
                            {{ __('Date') }}
                            @if ($sortField === 'created_at')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr wire:key="order-{{ $order->id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input
                                type="checkbox"
                                x-model="bulkSelected"
                                :value="{{ $order->id }}"
                                class="rounded border-gray-300"
                                aria-label="{{ __('Select order :param_1', ['param_1' => $order->order_number]) }}"
                            />
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/orders/{{ $order->id }}" wire:navigate class="text-sm font-mono text-blue-600 hover:underline">
                                #{{ $order->order_number }}
                            </a>
                            @if ($order->is_gift)
                                <span class="ml-1 text-xs text-pink-600" title="{{ __('Gift order for :param_1', ['param_1' => $order->customer->name]) }}">{{ __('Gift') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <img
                                    src="/avatars/{{ $order->customer->email }}.jpg"
                                    alt="{{ __('Profile picture of :param_1', ['param_1' => $order->customer->name]) }}"
                                    class="w-8 h-8 rounded-full"
                                />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $order->customer->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->customer->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if ($order->items_count === 1)
                                {{ __('1 item') }}
                            @else
                                {{ $order->items_count }} {{ __('Items') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            ${{ number_format($order->total, 2) }}
                            @if ($order->discount_amount > 0)
                                <span class="text-xs text-green-600 block">{{ __('Discount applied') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $badge = $statusBadges[$order->status] ?? ['class' => 'bg-gray-100 text-gray-800', 'label' => __('Unknown')]; @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $priorityLabels[$order->shipping_method] ?? $order->shipping_method }}
                            @if ($order->tracking_number)
                                <a
                                    href="https://track.example.com/{{ $order->tracking_number }}"
                                    target="_blank"
                                    class="block text-xs text-blue-500 hover:underline"
                                    title="{{ __('Track shipment for order #:param_1', ['param_1' => $order->order_number]) }}"
                                    aria-label="{{ __('Track shipment for order :param_1', ['param_1' => $order->order_number]) }}"
                                >
                                    {{ __('Track Package &rarr;') }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $order->created_at->format('M d, Y') }}
                            <span class="block text-xs">{{ $order->created_at->format('g:i A') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if ($order->status === 'processing')
                                    <button
                                        wire:click="markAsShipped({{ $order->id }})"
                                        wire:confirm="{{ __('Ship order #:param_1? The customer will receive a tracking notification.', ['param_1' => $order->order_number]) }}"
                                        class="text-sm text-blue-600 hover:underline"
                                        title="{{ __('Ship this order') }}"
                                    >
                                        {{ __('Ship Order') }}
                                    </button>
                                    <span class="text-gray-300">{{ __('&nbsp;&nbsp;') }}</span>
                                @endif

                                @if ($order->status === 'pending')
                                    <button
                                        wire:click="capturePayment({{ $order->id }})"
                                        wire:confirm="{{ __('Capture payment of $:param_1 for order #:param_2?', ['param_1' => number_format($order->total, 2), 'param_2' => $order->order_number]) }}"
                                        class="text-sm text-green-600 hover:underline"
                                    >
                                        {{ __('Capture Payment') }}
                                    </button>
                                    <span class="text-gray-300">{{ __('&nbsp;&nbsp;') }}</span>
                                @endif

                                @if (in_array($order->status, ['delivered', 'shipped']))
                                    <button
                                        @click="showRefundModal = true; selectedOrder = {{ $order->id }}; $dispatch('notify', { message: 'Refund modal opened for order #{{ $order->order_number }}', title: 'Info' })"
                                        class="text-sm text-orange-600 hover:underline"
                                    >
                                        {{ __('Issue Refund') }}
                                    </button>
                                    <span class="text-gray-300">{{ __('&nbsp;&nbsp;') }}</span>
                                @endif

                                @if ($order->status !== 'cancelled' && $order->status !== 'refunded')
                                    <button
                                        wire:click="cancelOrder({{ $order->id }})"
                                        wire:confirm.prompt="{{ __('Type CANCEL to cancel order #:param_1. The customer will be refunded and notified automatically.', ['param_1' => $order->order_number]) }}|CANCEL"
                                        class="text-sm text-red-600 hover:underline"
                                    >
                                        {{ __('Cancel') }}
                                    </button>
                                    <span class="text-gray-300">{{ __('&nbsp;&nbsp;') }}</span>
                                @endif

                                <button
                                    wire:click="deleteOrder({{ $order->id }})"
                                    wire:confirm.prompt="{{ __('This will permanently delete order #:param_1 and all associated records. Type DELETE to confirm.', ['param_1' => $order->order_number]) }}|DELETE"
                                    class="text-sm text-red-800 hover:underline"
                                >
                                    {{ __('Delete') }}
                                </button>
                                <span class="text-gray-300">{{ __('&nbsp;&nbsp;') }}</span>
                                <a
                                    href="/admin/orders/{{ $order->id }}"
                                    wire:navigate
                                    class="text-sm text-gray-500 hover:underline"
                                    title="{{ __('View full details for order #:param_1', ['param_1' => $order->order_number]) }}"
                                >
                                    {{ __('Details &rarr;') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center">
                            <img src="/images/empty-orders.svg" alt="{{ __('No orders illustration') }}" class="w-32 h-32 mx-auto mb-4 opacity-50" />
                            <p class="text-gray-500 text-lg font-medium">{{ __('No orders found') }}</p>
                            <p class="text-gray-400 text-sm mt-1">{{ __('Try adjusting your filters or check back later for new orders.') }}</p>
                            @if ($search)
                                <button
                                    wire:click="$set('search', '')"
                                    class="mt-3 text-sm text-blue-600 hover:underline"
                                >
                                    {{ __('Clear search and show all orders') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    {{ __('Showing') }} {{ $orders->firstItem() }} {{ __('to') }} {{ $orders->lastItem() }} {{ __('of') }} {{ $orders->total() }} {{ __('orders') }}
                </p>
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Export Confirmation Modal --}}
    <div
        x-show="showExportModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @keydown.escape.window="showExportModal = false"
    >
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6" @click.outside="showExportModal = false">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Export Orders') }}</h3>
            <p class="text-sm text-gray-600 mb-4" x-text="`${exportCount} {{ __('orders will be exported to CSV. This may take a moment.') }}`"></p>

            <div class="flex items-center justify-end gap-3">
                <button
                    @click="showExportModal = false; $dispatch('close-modal')"
                    class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    @click="$wire.exportOrders(); showExportModal = false; $dispatch('notify', { message: '{{ __('Export started — you will receive an email when ready') }}', title: '{{ __('Export in progress') }}' })"
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    {{ __('Start Export &rarr;') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Refund Modal --}}
    <div
        x-show="showRefundModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @keydown.escape.window="showRefundModal = false"
    >
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" @click.outside="showRefundModal = false">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Process Refund') }}</h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ __('Please provide a reason for the refund. The customer will receive a confirmation email once processed.') }}
            </p>

            <div class="mb-4">
                <label for="refund-reason" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Reason for refund') }}</label>
                <select id="refund-reason" x-model="refundReason" class="w-full rounded-lg border-gray-300 shadow-sm">
                    <option value="">{{ __('Select a reason...') }}</option>
                    <option value="defective">{{ __('Defective or damaged product') }}</option>
                    <option value="wrong_item">{{ __('Wrong item received') }}</option>
                    <option value="not_as_described">{{ __('Item not as described') }}</option>
                    <option value="changed_mind">{{ __('Customer changed their mind') }}</option>
                    <option value="duplicate">{{ __('Duplicate order') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="refund-notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Additional notes') }}</label>
                <textarea
                    id="refund-notes"
                    wire:model="refundNotes"
                    rows="3"
                    placeholder="{{ __('Add any relevant details about this refund...') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm"
                    aria-placeholder="{{ __('Enter refund details') }}"
                ></textarea>
            </div>

            <x-input
                type="number"
                wire:model="refundAmount"
                :label="__('Refund Amount')"
                :placeholder="__('Enter amount')"
                :description="__('Leave blank for a full refund')"
                aria-label="{{ __('Refund amount in dollars') }}"
            />

            <livewire:shared.currency-selector
                :label="__('Select currency')"
                :default="'USD'"
                :description="__('Currency used for the original transaction')"
            />

            <x-button
                :title="$refundAmount ? __('Partial refund of $') . number_format($refundAmount, 2) : __('Full refund will be issued')"
                :aria-label="__('Submit refund request')"
            >
                {{ __('Process') }}
            </x-button>

            <x-dropdown
                :placeholder="$refundAmount ? __('Choose items to refund') : __('Full refund selected')"
            />

            <x-alert
                :title="__('Refund Policy Notice')"
                :description="__('Refunds are typically processed within 5-7 business days. The customer will be notified via email.')"
            />

            <div class="flex items-center justify-end gap-3 mt-6">
                <button
                    @click="showRefundModal = false; $dispatch('close-modal')"
                    class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    wire:click="processRefund(selectedOrder)"
                    wire:confirm.prompt="{{ __('This will process the refund and credit the customer\'s payment method. Type CONFIRM to proceed.') }}|CONFIRM"
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700"
                    :disabled="!refundReason"
                >
                    {{ __('Confirm Refund') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Order Line Item Removal (Alpine template literal confirm) --}}
    @foreach ($orders as $order)
        @if ($order->status === 'processing')
            <div x-data="{ items: @js($order->items_count) }" class="hidden">
                <button
                    @click="if (confirm(`{{ __('Remove') }} ${items} item(s) from order #{{ $order->order_number }}?`)) { $wire.removeItems({{ $order->id }}) }"
                    class="text-sm text-red-500"
                >
                    {{ __('Remove Items') }}
                </button>
            </div>
        @endif
    @endforeach

    {{-- Keyboard Shortcuts & Footer --}}
    @auth
        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-400">
            <button
                @click="$dispatch('show-shortcuts')"
                class="hover:text-gray-600"
                title="{{ __('View available keyboard shortcuts') }}"
            >
                {{ __('Press ? for keyboard shortcuts') }}
            </button>
            <span>&nbsp;&mdash;&nbsp;</span>
            <button
                @click="$dispatch('refresh')"
                class="hover:text-gray-600"
            >
                {{ __('Refresh data') }}
            </button>
        </div>
    @endauth

    @guest
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                {{ __('You are viewing this page in read-only mode.') }}
                <a href="{{ route('login') }}" class="text-blue-600 hover:underline">{{ __('Sign in') }}</a>
                {{ __('to manage orders.') }}
            </p>
        </div>
    @endguest

    <script>
        // Do not translate anything in script tags
        document.addEventListener('order-shipped', () => {
            console.log( @json(__("Order shipped successfully")));
        });
    </script>

    <style>
        /* Styles should also be skipped */
        .order-row-highlight { background-color: #fefce8; }
    </style>

    {{-- This comment should be skipped by the scanner --}}

    @php
        // PHP blocks should be skipped
        $exportTimestamp = now()->format(__('Y-m-d_H-i-s'));
        $filename = "orders_export_{$exportTimestamp}.csv";
    @endphp

    <pre class="hidden">
        Debug info — this entire block should be skipped.
        Order count: {{ $orders->total() }}
    </pre>
</div>
