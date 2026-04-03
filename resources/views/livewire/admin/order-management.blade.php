<div
    class="max-w-7xl mx-auto py-8 px-4"
    x-data="{
        showRefundModal: false,
        selectedOrder: null,
        refundReason: '',
        bulkSelected: [],
        viewMode: 'table',
    }"
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
            <h1 class="text-2xl font-bold text-gray-900">Order Management</h1>
            <p class="text-gray-600 mt-1">Review, fulfill, and manage customer orders across all channels.</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                wire:click="exportOrders"
                title="Download orders as CSV"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm font-medium"
            >
                Export Orders
            </button>
            <button
                wire:click="syncInventory"
                title="Sync stock levels with warehouse"
                aria-label="Synchronize inventory"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium"
            >
                Sync Inventory
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">Total Orders</span>
            <p class="text-2xl font-bold">{{ $totalOrders }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">Awaiting Fulfillment</span>
            <p class="text-2xl font-bold text-yellow-600">{{ $awaitingCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">Shipped Today</span>
            <p class="text-2xl font-bold text-blue-600">{{ $shippedTodayCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">Revenue This Month</span>
            <p class="text-2xl font-bold text-green-600">${{ number_format($monthlyRevenue, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">Refund Requests</span>
            <p class="text-2xl font-bold text-red-600">{{ $refundRequestCount }}</p>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label for="order-search" class="sr-only">Search orders</label>
                <input
                    wire:model.live.debounce.300ms="search"
                    id="order-search"
                    type="search"
                    placeholder="Search by order number, customer name, or email..."
                    class="w-full rounded-lg border-gray-300 shadow-sm"
                />
            </div>
            <div>
                <label for="status-filter" class="sr-only">Filter by status</label>
                <select wire:model.live="statusFilter" id="status-filter" class="rounded-lg border-gray-300 shadow-sm">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending Payment</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div>
                <label for="channel-filter" class="sr-only">Filter by sales channel</label>
                <select wire:model.live="channelFilter" id="channel-filter" class="rounded-lg border-gray-300 shadow-sm">
                    <option value="all">All Channels</option>
                    <option value="web">Website</option>
                    <option value="mobile">Mobile App</option>
                    <option value="pos">In-Store POS</option>
                    <option value="marketplace">Marketplace</option>
                </select>
            </div>
            <div>
                <x-input
                    type="date"
                    wire:model.live="dateFrom"
                    :placeholder="'Start date'"
                    aria-label="Filter from date"
                />
            </div>
            <div>
                <x-input
                    type="date"
                    wire:model.live="dateTo"
                    :placeholder="'End date'"
                    aria-label="Filter to date"
                />
            </div>
        </div>

        {{-- Active Filter Tags --}}
        @if ($statusFilter !== 'all' || $channelFilter !== 'all' || $search)
            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                <span class="text-xs text-gray-500">Active filters:</span>
                @if ($search)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                        Search: {{ $search }}
                        <button wire:click="$set('search', '')" class="hover:text-blue-900">&times;</button>
                    </span>
                @endif
                <button wire:click="clearFilters" class="text-xs text-red-500 hover:underline ml-auto">
                    Clear all filters
                </button>
            </div>
        @endif
    </div>

    {{-- Bulk Actions --}}
    <div x-show="bulkSelected.length > 0" x-cloak class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-4 flex items-center justify-between">
        <span class="text-sm text-indigo-700" x-text="`${bulkSelected.length} orders selected`"></span>
        <div class="flex items-center gap-2">
            <button
                wire:click="bulkMarkShipped"
                class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700"
                title="Mark selected orders as shipped"
            >
                Mark as Shipped
            </button>
            <button
                wire:click="bulkPrintLabels"
                class="text-sm bg-gray-600 text-white px-3 py-1.5 rounded-lg hover:bg-gray-700"
            >
                Print Shipping Labels
            </button>
            <button
                @click="bulkSelected = []"
                class="text-sm text-gray-500 hover:text-gray-700"
            >
                Deselect All
            </button>
        </div>
    </div>

    @php
        $statusBadges = [
            'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'label' => 'Pending Payment'],
            'processing' => ['class' => 'bg-blue-100 text-blue-800', 'label' => 'Processing'],
            'shipped' => ['class' => 'bg-indigo-100 text-indigo-800', 'label' => 'Shipped'],
            'delivered' => ['class' => 'bg-green-100 text-green-800', 'label' => 'Delivered'],
            'cancelled' => ['class' => 'bg-gray-100 text-gray-800', 'label' => 'Cancelled'],
            'refunded' => ['class' => 'bg-red-100 text-red-800', 'label' => 'Refunded'],
        ];

        $priorityLabels = [
            'express' => 'Express Shipping',
            'standard' => 'Standard Delivery',
            'pickup' => 'Store Pickup',
        ];
    @endphp

    {{-- Orders Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" class="rounded border-gray-300" aria-label="Select all orders" />
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('order_number')" class="hover:text-gray-900 flex items-center gap-1">
                            Order Number
                            @if ($sortField === 'order_number')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('total')" class="hover:text-gray-900 flex items-center gap-1">
                            Total
                            @if ($sortField === 'total')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shipping</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('created_at')" class="hover:text-gray-900 flex items-center gap-1">
                            Date
                            @if ($sortField === 'created_at')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                                aria-label="Select order {{ $order->order_number }}"
                            />
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/orders/{{ $order->id }}" wire:navigate class="text-sm font-mono text-blue-600 hover:underline">
                                #{{ $order->order_number }}
                            </a>
                            @if ($order->is_gift)
                                <span class="ml-1 text-xs text-pink-600" title="This order is a gift">Gift</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $order->customer->name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->customer->email }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if ($order->items_count === 1)
                                1 item
                            @else
                                {{ $order->items_count }} items
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            ${{ number_format($order->total, 2) }}
                            @if ($order->discount_amount > 0)
                                <span class="text-xs text-green-600 block">Discount applied</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $badge = $statusBadges[$order->status] ?? ['class' => 'bg-gray-100 text-gray-800', 'label' => 'Unknown']; @endphp
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
                                    title="Track this shipment"
                                    aria-label="Track shipment for order {{ $order->order_number }}"
                                >
                                    Track Package
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $order->created_at->format('M d, Y') }}
                            <span class="block text-xs">{{ $order->created_at->format('g:i A') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($order->status === 'processing')
                                    <button
                                        wire:click="markAsShipped({{ $order->id }})"
                                        wire:confirm="Mark this order as shipped? The customer will receive a notification email."
                                        class="text-sm text-blue-600 hover:underline"
                                        title="Ship this order"
                                    >
                                        Ship Order
                                    </button>
                                @endif

                                @if ($order->status === 'pending')
                                    <button
                                        wire:click="capturePayment({{ $order->id }})"
                                        class="text-sm text-green-600 hover:underline"
                                    >
                                        Capture Payment
                                    </button>
                                @endif

                                @if (in_array($order->status, ['delivered', 'shipped']))
                                    <button
                                        @click="showRefundModal = true; selectedOrder = {{ $order->id }}"
                                        class="text-sm text-orange-600 hover:underline"
                                    >
                                        Issue Refund
                                    </button>
                                @endif

                                @if ($order->status !== 'cancelled' && $order->status !== 'refunded')
                                    <button
                                        wire:click="cancelOrder({{ $order->id }})"
                                        wire:confirm.prompt="Type CANCEL to cancel this order. The customer will be refunded and notified automatically.|CANCEL"
                                        class="text-sm text-red-600 hover:underline"
                                    >
                                        Cancel
                                    </button>
                                @endif

                                <button
                                    wire:click="deleteOrder({{ $order->id }})"
                                    wire:confirm.prompt="This will permanently delete order #{{ $order->order_number }} and all associated records. Type DELETE to confirm.|DELETE"
                                    class="text-sm text-red-800 hover:underline"
                                >
                                    Delete Permanently
                                </button>

                                <a
                                    href="/admin/orders/{{ $order->id }}"
                                    wire:navigate
                                    class="text-sm text-gray-500 hover:underline"
                                    title="View full order details"
                                >
                                    View Details
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center">
                            <img src="/images/empty-orders.svg" alt="No orders illustration" class="w-32 h-32 mx-auto mb-4 opacity-50" />
                            <p class="text-gray-500 text-lg font-medium">No orders found</p>
                            <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or check back later for new orders.</p>
                            @if ($search)
                                <button
                                    wire:click="$set('search', '')"
                                    class="mt-3 text-sm text-blue-600 hover:underline"
                                >
                                    Clear search and show all orders
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
                    Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                </p>
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Refund Modal --}}
    <div
        x-show="showRefundModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @keydown.escape.window="showRefundModal = false"
    >
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" @click.outside="showRefundModal = false">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Process Refund</h3>
            <p class="text-sm text-gray-600 mb-4">
                Please provide a reason for the refund. The customer will receive a confirmation email once processed.
            </p>

            <div class="mb-4">
                <label for="refund-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for refund</label>
                <select id="refund-reason" x-model="refundReason" class="w-full rounded-lg border-gray-300 shadow-sm">
                    <option value="">Select a reason...</option>
                    <option value="defective">Defective or damaged product</option>
                    <option value="wrong_item">Wrong item received</option>
                    <option value="not_as_described">Item not as described</option>
                    <option value="changed_mind">Customer changed their mind</option>
                    <option value="duplicate">Duplicate order</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="refund-notes" class="block text-sm font-medium text-gray-700 mb-1">Additional notes</label>
                <textarea
                    id="refund-notes"
                    wire:model="refundNotes"
                    rows="3"
                    placeholder="Add any relevant details about this refund..."
                    class="w-full rounded-lg border-gray-300 shadow-sm"
                    aria-placeholder="Enter refund details"
                ></textarea>
            </div>

            <x-input
                type="number"
                wire:model="refundAmount"
                :label="__('Refund Amount')"
                :placeholder="'Enter amount'"
                :description="'Leave blank for a full refund'"
                aria-label="Refund amount in dollars"
            />

            <livewire:shared.currency-selector
                :label="'Select currency'"
                :default="'USD'"
                :description="$order->currency ?? 'USD'"
            />

            <x-button
                :title="$hasShipped ? 'Return shipping required' : 'No return needed'"
                :aria-label="'Submit refund for order #' . $order->order_number"
            />

            <x-dropdown
                :placeholder="$isPartialRefund ? 'Choose items to refund' : 'Full refund selected'"
            />

            <x-alert
                :title="'Refund Policy Notice'"
                :description="'Refunds are typically processed within 5-7 business days. The customer will be notified via email.'"
            />

            <div class="flex items-center justify-end gap-3 mt-6">
                <button
                    @click="showRefundModal = false"
                    class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900"
                >
                    Cancel
                </button>
                <button
                    wire:click="processRefund(selectedOrder)"
                    wire:confirm.prompt="This will process the refund and credit the customer's payment method. Type CONFIRM to proceed.|CONFIRM"
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700"
                    :disabled="!refundReason"
                >
                    Confirm Refund
                </button>
            </div>
        </div>
    </div>

    {{-- Keyboard Shortcuts Help --}}
    @auth
        <div class="mt-6 text-center">
            <button
                x-data
                @click="$dispatch('show-shortcuts')"
                class="text-xs text-gray-400 hover:text-gray-600"
                title="View available keyboard shortcuts"
            >
                Press ? for keyboard shortcuts
            </button>
        </div>
    @endauth

    @guest
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                You are viewing this page in read-only mode.
                <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Sign in</a>
                to manage orders.
            </p>
        </div>
    @endguest

    <script>
        // Do not translate anything in script tags
        document.addEventListener('order-shipped', () => {
            console.log('Order shipped successfully');
        });
    </script>

    <style>
        /* Styles should also be skipped */
        .order-row-highlight { background-color: #fefce8; }
    </style>

    {{-- This comment should be skipped by the scanner --}}

    @php
        // PHP blocks should be skipped
        $exportTimestamp = now()->format('Y-m-d_H-i-s');
        $filename = "orders_export_{$exportTimestamp}.csv";
    @endphp

    <pre class="hidden">
        Debug info - this entire block should be skipped.
        Order count: {{ $orders->total() }}
    </pre>
</div>
