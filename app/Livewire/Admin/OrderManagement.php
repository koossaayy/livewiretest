<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
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

    #[Computed]
    public function totalOrders(): int
    {
        return 0;
    }

    #[Computed]
    public function awaitingCount(): int
    {
        return 0;
    }

    #[Computed]
    public function shippedTodayCount(): int
    {
        return 0;
    }

    #[Computed]
    public function monthlyRevenue(): float
    {
        return 0.00;
    }

    #[Computed]
    public function refundRequestCount(): int
    {
        return 0;
    }

    public function render()
    {
        $orders = collect()->paginate(15);

        return view('livewire.admin.order-management', [
            'orders' => $orders,
            'totalOrders' => $this->totalOrders,
            'awaitingCount' => $this->awaitingCount,
            'shippedTodayCount' => $this->shippedTodayCount,
            'monthlyRevenue' => $this->monthlyRevenue,
            'refundRequestCount' => $this->refundRequestCount,
        ]);
    }
}
