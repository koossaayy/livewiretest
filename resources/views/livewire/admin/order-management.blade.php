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
    x-on:order-saved.window="$dispatch('notify', { message: 'Order saved successfully', title: 'Success' })"
    x-on:item-removed.window="$dispatch('toast', { message: 'Item removed from cart', description: 'You can undo this action' })"
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
                @click="exportCount = bulkSelected.length || {{ $totalOrders }}; showExportModal = true"
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
                @click="if (confirm(`Export ${bulkSelected.length} selected orders to CSV?`)) { $wire.exportSelected(bulkSelected) }"
                class="text-sm bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700"
            >
                Export Selected
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
                                <span class="ml-1 text-xs text-pink-600" title="Gift order for {{ $order->customer->name }}">Gift</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <img
                                    src="/avatars/{{ $order->customer->email }}.jpg"
                                    alt="Profile picture of {{ $order->customer->name }}"
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
                                    title="Track shipment for order #{{ $order->order_number }}"
                                    aria-label="Track shipment for order {{ $order->order_number }}"
                                >
                                    Track Package &rarr;
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
                                        wire:confirm="Ship order #{{ $order->order_number }}? The customer will receive a tracking notification."
                                        class="text-sm text-blue-600 hover:underline"
                                        title="Ship this order"
                                    >
                                        Ship Order
                                    </button>
                                    <span class="text-gray-300">&nbsp;•&nbsp;</span>
                                @endif

                                @if ($order->status === 'pending')
                                    <button
                                        wire:click="capturePayment({{ $order->id }})"
                                        wire:confirm="Capture payment of ${{ number_format($order->total, 2) }} for order #{{ $order->order_number }}?"
                                        class="text-sm text-green-600 hover:underline"
                                    >
                                        Capture Payment
                                    </button>
                                    <span class="text-gray-300">&nbsp;•&nbsp;</span>
                                @endif

                                @if (in_array($order->status, ['delivered', 'shipped']))
                                    <button
                                        @click="showRefundModal = true; selectedOrder = {{ $order->id }}; $dispatch('notify', { message: 'Refund modal opened for order #{{ $order->order_number }}', title: 'Info' })"
                                        class="text-sm text-orange-600 hover:underline"
                                    >
                                        Issue Refund
                                    </button>
                                    <span class="text-gray-300">&nbsp;•&nbsp;</span>
                                @endif

                                @if ($order->status !== 'cancelled' && $order->status !== 'refunded')
                                    <button
                                        wire:click="cancelOrder({{ $order->id }})"
                                        wire:confirm.prompt="Type CANCEL to cancel order #{{ $order->order_number }}. The customer will be refunded and notified automatically.|CANCEL"
                                        class="text-sm text-red-600 hover:underline"
                                    >
                                        Cancel
                                    </button>
                                    <span class="text-gray-300">&nbsp;•&nbsp;</span>
                                @endif

                                <button
                                    wire:click="deleteOrder({{ $order->id }})"
                                    wire:confirm.prompt="This will permanently delete order #{{ $order->order_number }} and all associated records. Type DELETE to confirm.|DELETE"
                                    class="text-sm text-red-800 hover:underline"
                                >
                                    Delete
                                </button>
                                <span class="text-gray-300">&nbsp;•&nbsp;</span>
                                <a
                                    href="/admin/orders/{{ $order->id }}"
                                    wire:navigate
                                    class="text-sm text-gray-500 hover:underline"
                                    title="View full details for order #{{ $order->order_number }}"
                                >
                                    Details &rarr;
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

    {{-- Export Confirmation Modal --}}
    <div
        x-show="showExportModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @keydown.escape.window="showExportModal = false"
    >
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6" @click.outside="showExportModal = false">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Export Orders</h3>
            <p class="text-sm text-gray-600 mb-4" x-text="`${exportCount} orders will be exported to CSV. This may take a moment.`"></p>

            <div class="flex items-center justify-end gap-3">
                <button
                    @click="showExportModal = false; $dispatch('close-modal')"
                    class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900"
                >
                    Cancel
                </button>
                <button
                    @click="$wire.exportOrders(); showExportModal = false; $dispatch('notify', { message: 'Export started — you will receive an email when ready', title: 'Export in progress' })"
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    Start Export &rarr;
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
                :description="'Currency used for the original transaction'"
            />

            <x-button
                :title="$refundAmount ? 'Partial refund of $' . number_format($refundAmount, 2) : 'Full refund will be issued'"
                :aria-label="'Submit refund request'"
            >
                Process
            </x-button>

            <x-dropdown
                :placeholder="$refundAmount ? 'Choose items to refund' : 'Full refund selected'"
            />

            <x-alert
                :title="'Refund Policy Notice'"
                :description="'Refunds are typically processed within 5-7 business days. The customer will be notified via email.'"
            />

            <div class="flex items-center justify-end gap-3 mt-6">
                <button
                    @click="showRefundModal = false; $dispatch('close-modal')"
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

    {{-- Order Line Item Removal (Alpine template literal confirm) --}}
    @foreach ($orders as $order)
        @if ($order->status === 'processing')
            <div x-data="{ items: @js($order->items_count) }" class="hidden">
                <button
                    @click="if (confirm(`Remove ${items} item(s) from order #{{ $order->order_number }}?`)) { $wire.removeItems({{ $order->id }}) }"
                    class="text-sm text-red-500"
                >
                    Remove Items
                </button>
            </div>
        @endif
    @endforeach

    {{-- ============================================================== --}}
    {{-- EDGE CASE GAUNTLET — push the scanner to its limits             --}}
    {{-- ============================================================== --}}

    {{-- 1. Alpine x-data with inline string properties (should NOT be extracted — they're JS state) --}}
    <div
        x-data="{
            message: 'Are you sure?',
            confirmText: 'Yes, delete it',
            cancelText: 'No, keep it',
            status: 'idle',
            errorMessage: '',
            tooltipContent: 'Click to copy order number',
        }"
        class="hidden"
    >
        {{-- x-text with a variable — skip the x-text, but the fallback text IS visible --}}
        <p x-text="message">Loading order details...</p>
        <p x-text="errorMessage">No errors detected.</p>

        {{-- x-html — skip entirely, it's raw HTML injection --}}
        <div x-html="'<strong>Bold text</strong>'"></div>

        {{-- x-bind:title with a JS expression — skip --}}
        <span :title="confirmText">Hover for info</span>

        {{-- x-bind:aria-label with template literal — skip the Alpine part --}}
        <button :aria-label="`Order ${selectedOrder} actions`">Actions</button>

        {{-- x-show / x-if with string comparison — skip entirely --}}
        <div x-show="status === 'processing'">
            <p>Your order is being processed. Please wait.</p>
        </div>

        {{-- x-transition attributes — skip these, they're config not text --}}
        <div
            x-show="status === 'complete'"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
        >
            Order complete!
        </div>
    </div>

    {{-- 2. Alpine @click with complex expressions mixing strings --}}
    <div x-data="{ orderName: '', quantity: 0 }" class="space-y-2 mt-6">
        <button
            @click="
                if (quantity > 100) {
                    if (!confirm(`You are about to order ${quantity} units of '${orderName}'. This is a large order and may require manager approval. Continue?`)) return;
                }
                $dispatch('notify', { message: 'Order placed successfully', title: 'Done' });
                $dispatch('refresh');
            "
            class="bg-blue-600 text-white px-4 py-2 rounded-lg"
        >
            Place Large Order
        </button>

        <button
            @click="
                let msg = `Archiving ${bulkSelected.length} orders. This cannot be undone.`;
                if (confirm(msg)) {
                    $wire.archiveOrders(bulkSelected);
                    $dispatch('toast', { message: 'Orders archived', description: 'Archived orders can be found in the archive tab' });
                }
            "
            class="bg-gray-600 text-white px-4 py-2 rounded-lg"
        >
            Archive Selected
        </button>

        {{-- @click with no translatable content — just event dispatch --}}
        <button @click="$dispatch('open-sidebar')">
            Toggle Sidebar
        </button>

        {{-- @click with concatenation, not template literal --}}
        <button @click="alert('Order #' + selectedOrder + ' has been flagged for review')">
            Flag for Review
        </button>
    </div>

    {{-- 3. Deeply nested Alpine x-data scopes --}}
    <div
        x-data="orderNotifications()"
        x-init="init()"
        @order-updated.window="handleUpdate($event.detail)"
        class="mt-6"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <div class="p-3 bg-gray-50 rounded mb-2">
                {{-- x-text bindings — scanner should skip --}}
                <h4 x-text="notification.title" class="font-medium"></h4>
                <p x-text="notification.body" class="text-sm text-gray-600"></p>
                <span x-text="notification.timestamp" class="text-xs text-gray-400"></span>
                <button
                    @click="dismiss(notification.id); $dispatch('toast', { message: 'Notification dismissed', description: 'You can view dismissed notifications in settings' })"
                    class="text-xs text-red-500 ml-2"
                >
                    Dismiss
                </button>
            </div>
        </template>

        {{-- Empty state — this text SHOULD be extracted --}}
        <template x-if="notifications.length === 0">
            <p class="text-gray-400 text-sm text-center py-4">No new notifications. You're all caught up!</p>
        </template>
    </div>

    {{-- 4. Mixed static text with Blade variables in various positions --}}
    <div class="mt-8 bg-gray-50 rounded-xl p-6">
        <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>

        {{-- Inline text mixed with Blade — the static parts should be extracted --}}
        <p class="text-sm text-gray-600 mb-2">
            Welcome back, {{ auth()->user()->name }}! You have {{ $awaitingCount }} orders awaiting fulfillment.
        </p>

        <p class="text-sm text-gray-600 mb-4">
            Last sync: {{ now()->format('M d, Y \a\t g:i A') }} &nbsp;&bull;&nbsp; Next sync in 15 minutes
        </p>

        {{-- Pluralization edge cases --}}
        <p class="text-sm text-gray-500">
            @if ($totalOrders === 0)
                No orders have been placed yet.
            @elseif ($totalOrders === 1)
                There is 1 order in the system.
            @else
                There are {{ $totalOrders }} orders in the system.
            @endif
        </p>

        {{-- String with special characters --}}
        <p class="text-sm text-gray-500 mt-2">
            Need help? Contact support at support@example.com — we're here 24/7.
        </p>

        {{-- String that looks like code but is user-facing --}}
        <p class="text-xs text-gray-400 mt-1">
            Use the keyboard shortcut Ctrl+K to open the command palette.
        </p>

        {{-- Breadcrumb with entity separators — entities should NOT be extracted --}}
        <nav class="text-xs text-gray-400 mt-4" aria-label="Breadcrumb">
            <a href="/admin" class="hover:underline">Admin</a>
            <span>&nbsp;&rsaquo;&nbsp;</span>
            <a href="/admin/orders" class="hover:underline">Orders</a>
            <span>&nbsp;&rsaquo;&nbsp;</span>
            <span class="text-gray-600">Management</span>
        </nav>
    </div>

    {{-- 5. wire:confirm edge cases --}}
    <div class="mt-6 space-y-2">
        {{-- wire:confirm with single quotes inside the message --}}
        <button
            wire:click="resetFilters"
            wire:confirm="This will reset all filters to their default state. You'll lose any unsaved filter presets."
            class="text-sm text-gray-600"
        >
            Reset All Filters
        </button>

        {{-- wire:confirm with HTML entity in the message --}}
        <button
            wire:click="mergeOrders"
            wire:confirm="Merge selected orders into one? This action cannot be undone &mdash; all individual order numbers will be retired."
            class="text-sm text-blue-600"
        >
            Merge Orders
        </button>

        {{-- wire:confirm with numbers and currency --}}
        <button
            wire:click="issueCredit({{ $monthlyRevenue }})"
            wire:confirm="Issue a store credit of ${{ number_format($monthlyRevenue, 2) }} to the customer's account?"
            class="text-sm text-green-600"
        >
            Issue Store Credit
        </button>

        {{-- wire:confirm.prompt with special characters in the confirmation word --}}
        <button
            wire:click="purgeOldOrders"
            wire:confirm.prompt="This will permanently remove all orders older than 90 days. This frees up {{ $totalOrders }} records. Type PURGE to continue.|PURGE"
            class="text-sm text-red-700"
        >
            Purge Old Orders
        </button>

        {{-- wire:confirm that's basically empty (edge case) --}}
        <button
            wire:click="doSomething"
            wire:confirm="Continue?"
            class="text-sm text-gray-500"
        >
            Quick Action
        </button>
    </div>

    {{-- 6. Attributes with empty strings and edge cases --}}
    <div class="mt-6">
        {{-- Empty placeholder — should be skipped --}}
        <input type="text" placeholder="" class="rounded border-gray-300" />

        {{-- Placeholder with just a space — should be skipped --}}
        <input type="text" placeholder=" " class="rounded border-gray-300" />

        {{-- Placeholder with just an entity — should be skipped --}}
        <input type="text" placeholder="&hellip;" class="rounded border-gray-300" />

        {{-- title with just punctuation — should be skipped --}}
        <button title="...">More</button>

        {{-- aria-label with number only — should be skipped --}}
        <span aria-label="3">3</span>

        {{-- Real placeholder after the edge cases --}}
        <input
            type="email"
            placeholder="Enter customer email address..."
            aria-label="Customer email input"
            class="rounded border-gray-300 mt-2"
        />

        {{-- Multiple translatable attributes on one element --}}
        <input
            type="search"
            placeholder="Search inventory..."
            title="Search across all warehouse locations"
            aria-label="Inventory search"
            aria-roledescription="search field"
            class="rounded border-gray-300 mt-2"
        />
    </div>

    {{-- 7. Conditional rendering with strings in tricky positions --}}
    <div class="mt-6">
        @switch($statusFilter)
            @case('pending')
                <div class="bg-yellow-50 p-4 rounded">
                    <h3 class="font-semibold text-yellow-800">Pending Orders</h3>
                    <p class="text-sm text-yellow-700">These orders are waiting for payment confirmation.</p>
                </div>
                @break
            @case('processing')
                <div class="bg-blue-50 p-4 rounded">
                    <h3 class="font-semibold text-blue-800">Processing Orders</h3>
                    <p class="text-sm text-blue-700">These orders have been paid and are being prepared for shipment.</p>
                </div>
                @break
            @default
                <div class="bg-gray-50 p-4 rounded">
                    <h3 class="font-semibold text-gray-800">All Orders</h3>
                    <p class="text-sm text-gray-600">Showing all orders across every status and channel.</p>
                </div>
        @endswitch
    </div>

    {{-- 8. Component attributes — things to extract vs skip --}}
    <div class="mt-6 space-y-4">
        {{-- :label with concatenation and variable --}}
        <x-input
            type="text"
            wire:model="customerNote"
            :label="'Note for order #' . ($orders->first()?->order_number ?? 'N/A')"
            :placeholder="'Add a note for this customer...'"
            :description="'This note will be visible to the customer on their order confirmation.'"
        />

        {{-- Static attributes that should be extracted --}}
        <x-alert
            title="Shipping Delay Notice"
            description="Due to high demand, orders placed after 3 PM may experience a 1-2 day delay in processing."
        />

        {{-- Dynamic attribute with __() already applied — skip --}}
        <x-alert
            :title="__('System Maintenance')"
            :description="__('The order system will be undergoing maintenance tonight from 11 PM to 2 AM.')"
        />

        {{-- x-component with mixed static and dynamic --}}
        <x-button
            title="Create a new order manually"
            aria-label="Open new order form"
            :description="'For phone and in-person orders only'"
        >
            New Manual Order
        </x-button>

        {{-- Component with @js() in attribute — should be skipped --}}
        <div x-data="{ config: @js(['locale' => app()->getLocale(), 'currency' => 'USD']) }">
            <x-dropdown
                :placeholder="'Select shipping method'"
                :label="'Shipping Method'"
            />
        </div>
    </div>

    {{-- 9. Strings inside @json / @js — should be SKIPPED --}}
    <div
        x-data="{
            labels: @js(['pending' => 'Pending', 'shipped' => 'Shipped', 'delivered' => 'Delivered']),
            config: @json(['dateFormat' => 'Y-m-d', 'timezone' => 'UTC', 'emptyMessage' => 'No data available']),
        }"
        class="hidden"
    ></div>

    {{-- 10. Multi-line text blocks --}}
    <div class="mt-6 bg-blue-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">Bulk Import Instructions</h3>
        <p class="text-sm text-blue-800 mb-3">
            To import orders in bulk, prepare a CSV file with the following columns:
            order_number, customer_email, total, status, and shipping_method.
        </p>
        <p class="text-sm text-blue-700">
            Maximum file size is 10 MB. Orders with duplicate order numbers will be skipped.
            If you need help formatting your file, download the
            <a href="/templates/order-import.csv" class="underline">sample template</a>.
        </p>
        <button
            wire:click="showImportModal"
            class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"
        >
            Upload CSV File
        </button>
    </div>

    {{-- 11. Tricky inline JS that should NOT be extracted --}}
    <div
        x-data
        x-init="
            $watch('$store.orders.count', value => {
                if (value === 0) {
                    $dispatch('notify', { message: 'All orders have been processed', title: 'Queue empty' });
                }
            })
        "
        class="hidden"
    ></div>

    {{-- 12. Escaped quotes and apostrophes in wire:confirm --}}
    <button
        wire:click="archiveFlagged"
        wire:confirm="Archive all flagged orders? Items marked as &quot;suspicious&quot; will be sent to the fraud team."
        class="hidden"
    >
        Archive Flagged
    </button>

    {{-- 13. data-* attributes — should NOT be extracted (not user-visible) --}}
    <div
        data-tooltip="Order management panel"
        data-description="Main administrative interface for managing customer orders"
        data-empty-text="No orders to display"
        class="hidden"
    ></div>

    {{-- 14. Livewire event listeners with string payloads --}}
    <div
        x-data
        @order-created.window="$dispatch('toast', { message: 'New order received!', description: 'Check the pending tab for details' })"
        @payment-failed.window="$dispatch('notify', { message: 'Payment processing failed', title: 'Payment Error' })"
        @inventory-low.window="$dispatch('toast', { message: 'Inventory running low on some items', description: 'Review stock levels in the warehouse panel' })"
        class="hidden"
    ></div>

    {{-- Keyboard Shortcuts & Footer --}}
    @auth
        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-400">
            <button
                @click="$dispatch('show-shortcuts')"
                class="hover:text-gray-600"
                title="View available keyboard shortcuts"
            >
                Press ? for keyboard shortcuts
            </button>
            <span>&nbsp;&mdash;&nbsp;</span>
            <button
                @click="$dispatch('refresh')"
                class="hover:text-gray-600"
            >
                Refresh data
            </button>
            <span>&nbsp;&mdash;&nbsp;</span>
            <span>v2.4.1</span>
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

    {{-- 15. Script tag with strings that look translatable — must NOT be extracted --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Order management loaded');

            const messages = {
                success: 'Operation completed successfully',
                error: 'Something went wrong. Please try again.',
                confirm: 'Are you sure you want to proceed?',
                empty: 'No orders found matching your criteria',
                loading: 'Loading orders...',
            };

            window.orderConfig = {
                pageTitle: 'Order Management Dashboard',
                emptyStateMessage: 'Start by creating your first order',
                exportFilename: 'orders_export',
            };

            function showNotification(type, message) {
                const el = document.createElement('div');
                el.textContent = message;
                el.className = `notification notification-${type}`;
                document.body.appendChild(el);
                setTimeout(() => el.remove(), 3000);
            }
        });
    </script>

    {{-- 16. Style tag with content that looks like text — must NOT be extracted --}}
    <style>
        .order-row-highlight { background-color: #fefce8; }
        .status-badge::after { content: '●'; }
        .empty-state::before { content: 'No items'; display: none; }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
        }
    </style>

    {{-- 17. HTML comments — must NOT be extracted --}}
    {{-- TODO: Add bulk discount feature by end of Q2 --}}
    {{-- FIXME: The export button sometimes generates empty CSV files --}}
    {{-- This entire comment should be completely invisible to the scanner --}}

    @php
        // PHP block — should be skipped entirely
        $exportTimestamp = now()->format('Y-m-d_H-i-s');
        $filename = "orders_export_{$exportTimestamp}.csv";
        $warningThreshold = 50;
        $criticalMessage = 'System capacity exceeded';
    @endphp

    {{-- 18. <code> and <pre> blocks — should NOT be extracted --}}
    <pre class="hidden bg-gray-900 text-green-400 p-4 rounded font-mono text-xs">
        $ php artisan orders:process --batch-size=100
        Processing 100 orders...
        [OK] All orders processed successfully.
        Debug info — Order count: {{ $orders->total() }}
    </pre>

    <code class="hidden">wire:confirm="This is inside a code tag and should not be extracted"</code>

    {{-- 19. Conditional class with text-like values — skip these, they're CSS classes --}}
    <div class="{{ $totalOrders > 100 ? 'bg-red-warning-high-volume' : 'bg-green-normal-volume' }}"></div>

    {{-- 20. Blade components with slot content --}}
    <x-alert title="Important Update">
        The order processing system has been upgraded. All orders placed before January 1st
        have been migrated to the new format. If you notice any discrepancies, please contact
        the engineering team.
    </x-alert>
</div>
