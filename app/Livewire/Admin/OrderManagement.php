<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
#[Title('Order Management')]
class OrderManagement extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $statusFilter = 'all';
    public string $channelFilter = 'all';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public string $refundNotes = '';
    public ?float $refundAmount = null;

    private function getFakeOrders(): array
    {
        return [
            (object) [
                'id' => 1,
                'order_number' => 'ORD-2024-001',
                'status' => 'processing',
                'total' => 249.99,
                'discount_amount' => 0,
                'items_count' => 3,
                'shipping_method' => 'express',
                'tracking_number' => null,
                'is_gift' => false,
                'channel' => 'web',
                'created_at' => Carbon::parse('2026-04-01 10:30:00'),
                'customer' => (object) ['name' => 'Sarah Johnson', 'email' => 'sarah@example.com'],
            ],
            (object) [
                'id' => 2,
                'order_number' => 'ORD-2024-002',
                'status' => 'shipped',
                'total' => 89.50,
                'discount_amount' => 10.00,
                'items_count' => 1,
                'shipping_method' => 'standard',
                'tracking_number' => 'TRK-9182736',
                'is_gift' => true,
                'channel' => 'mobile',
                'created_at' => Carbon::parse('2026-03-28 14:15:00'),
                'customer' => (object) ['name' => 'Mike Chen', 'email' => 'mike.chen@example.com'],
            ],
            (object) [
                'id' => 3,
                'order_number' => 'ORD-2024-003',
                'status' => 'pending',
                'total' => 1250.00,
                'discount_amount' => 0,
                'items_count' => 7,
                'shipping_method' => 'express',
                'tracking_number' => null,
                'is_gift' => false,
                'channel' => 'web',
                'created_at' => Carbon::parse('2026-04-03 09:00:00'),
                'customer' => (object) ['name' => 'Emma Williams', 'email' => 'emma.w@example.com'],
            ],
            (object) [
                'id' => 4,
                'order_number' => 'ORD-2024-004',
                'status' => 'delivered',
                'total' => 34.99,
                'discount_amount' => 5.00,
                'items_count' => 2,
                'shipping_method' => 'standard',
                'tracking_number' => 'TRK-5647382',
                'is_gift' => false,
                'channel' => 'pos',
                'created_at' => Carbon::parse('2026-03-20 16:45:00'),
                'customer' => (object) ['name' => 'James Rodriguez', 'email' => 'j.rodriguez@example.com'],
            ],
            (object) [
                'id' => 5,
                'order_number' => 'ORD-2024-005',
                'status' => 'cancelled',
                'total' => 199.00,
                'discount_amount' => 0,
                'items_count' => 4,
                'shipping_method' => 'pickup',
                'tracking_number' => null,
                'is_gift' => false,
                'channel' => 'marketplace',
                'created_at' => Carbon::parse('2026-03-25 11:20:00'),
                'customer' => (object) ['name' => 'Lisa Park', 'email' => 'lisa.park@example.com'],
            ],
            (object) [
                'id' => 6,
                'order_number' => 'ORD-2024-006',
                'status' => 'refunded',
                'total' => 450.00,
                'discount_amount' => 50.00,
                'items_count' => 2,
                'shipping_method' => 'express',
                'tracking_number' => 'TRK-1029384',
                'is_gift' => true,
                'channel' => 'web',
                'created_at' => Carbon::parse('2026-03-15 08:30:00'),
                'customer' => (object) ['name' => 'David Kim', 'email' => 'david.kim@example.com'],
            ],
            (object) [
                'id' => 7,
                'order_number' => 'ORD-2024-007',
                'status' => 'processing',
                'total' => 75.00,
                'discount_amount' => 0,
                'items_count' => 1,
                'shipping_method' => 'standard',
                'tracking_number' => null,
                'is_gift' => false,
                'channel' => 'mobile',
                'created_at' => Carbon::parse('2026-04-04 13:00:00'),
                'customer' => (object) ['name' => 'Anna Müller', 'email' => 'anna.m@example.com'],
            ],
        ];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'channelFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function exportOrders(): void
    {
        try {
            Log::info('Order export initiated', ['admin_id' => auth()->id()]);
            session()->flash('success', 'Order export is being prepared. You will receive an email when ready.');
        } catch (\Exception $e) {
            Log::error('Order export failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to start export. Please try again.');
        }
    }

    public function syncInventory(): void
    {
        session()->flash('success', 'Inventory sync has been queued. This may take a few minutes.');
    }

    public function markAsShipped(int $orderId): void
    {
        session()->flash('success', 'Order has been marked as shipped. Customer notification sent.');
    }

    public function capturePayment(int $orderId): void
    {
        session()->flash('success', 'Payment captured successfully.');
    }

    public function cancelOrder(int $orderId): void
    {
        session()->flash('success', 'Order has been cancelled and the customer has been notified.');
    }

    public function deleteOrder(int $orderId): void
    {
        session()->flash('success', 'Order has been permanently deleted.');
    }

    public function processRefund(int $orderId): void
    {
        session()->flash('success', 'Refund has been processed. The customer will receive confirmation shortly.');
        $this->reset(['refundNotes', 'refundAmount']);
    }

    public function bulkMarkShipped(): void
    {
        session()->flash('success', 'Selected orders have been marked as shipped.');
    }

    public function bulkPrintLabels(): void
    {
        session()->flash('success', 'Shipping labels are being generated.');
    }

    public function exportSelected(array $orderIds): void
    {
        session()->flash('success', count($orderIds) . ' orders are being exported.');
    }

    public function removeItems(int $orderId): void
    {
        session()->flash('success', 'Items have been removed from the order.');
    }

    public function render()
    {
        $allOrders = collect($this->getFakeOrders());

        $filtered = $allOrders
            ->when($this->search, fn ($c) => $c->filter(fn ($o) =>
                str_contains(strtolower($o->order_number), strtolower($this->search)) ||
                str_contains(strtolower($o->customer->name), strtolower($this->search)) ||
                str_contains(strtolower($o->customer->email), strtolower($this->search))
            ))
            ->when($this->statusFilter !== 'all', fn ($c) => $c->where('status', $this->statusFilter))
            ->when($this->channelFilter !== 'all', fn ($c) => $c->where('channel', $this->channelFilter));

        $sorted = $filtered->sortBy($this->sortField, SORT_REGULAR, $this->sortDirection === 'desc');

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $orders = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.admin.order-management', [
            'orders' => $orders,
            'totalOrders' => $allOrders->count(),
            'awaitingCount' => $allOrders->where('status', 'processing')->count(),
            'shippedTodayCount' => $allOrders->where('status', 'shipped')->count(),
            'monthlyRevenue' => $allOrders->whereNotIn('status', ['cancelled', 'refunded'])->sum('total'),
            'refundRequestCount' => $allOrders->where('status', 'refunded')->count(),
        ]);
    }
}
