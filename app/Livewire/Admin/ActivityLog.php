<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

#[Layout('layouts.app')]
#[Title('Activity Log')]
class ActivityLog extends Component
{
    #[Url]
    public string $search = '';

    public string $typeFilter = 'all';
    public string $dateRange = 'week';

    private function getFakeActivities(): array
    {
        return [
            (object) [
                'id' => 1,
                'type' => 'order',
                'action' => 'created',
                'description' => 'New order #ORD-2024-008 placed',
                'user' => (object) ['name' => 'Sarah Johnson', 'email' => 'sarah@example.com'],
                'metadata' => (object) ['ip' => '192.168.1.42', 'browser' => 'Chrome 120'],
                'created_at' => Carbon::now()->subMinutes(12),
            ],
            (object) [
                'id' => 2,
                'type' => 'user',
                'action' => 'login',
                'description' => 'User logged in from new device',
                'user' => (object) ['name' => 'Mike Chen', 'email' => 'mike.chen@example.com'],
                'metadata' => (object) ['ip' => '10.0.0.15', 'browser' => 'Firefox 121'],
                'created_at' => Carbon::now()->subMinutes(34),
            ],
            (object) [
                'id' => 3,
                'type' => 'order',
                'action' => 'shipped',
                'description' => 'Order #ORD-2024-003 marked as shipped',
                'user' => (object) ['name' => 'Admin', 'email' => 'admin@example.com'],
                'metadata' => (object) ['ip' => '192.168.1.1', 'browser' => 'Safari 17'],
                'created_at' => Carbon::now()->subHours(2),
            ],
            (object) [
                'id' => 4,
                'type' => 'system',
                'action' => 'backup',
                'description' => 'Automatic database backup completed',
                'user' => (object) ['name' => 'System', 'email' => 'system@internal'],
                'metadata' => (object) ['ip' => '127.0.0.1', 'browser' => 'N/A'],
                'created_at' => Carbon::now()->subHours(6),
            ],
            (object) [
                'id' => 5,
                'type' => 'user',
                'action' => 'updated',
                'description' => 'Profile settings changed',
                'user' => (object) ['name' => 'Emma Williams', 'email' => 'emma.w@example.com'],
                'metadata' => (object) ['ip' => '172.16.0.88', 'browser' => 'Edge 119'],
                'created_at' => Carbon::now()->subHours(8),
            ],
            (object) [
                'id' => 6,
                'type' => 'payment',
                'action' => 'refunded',
                'description' => 'Refund of $89.50 processed for order #ORD-2024-002',
                'user' => (object) ['name' => 'Admin', 'email' => 'admin@example.com'],
                'metadata' => (object) ['ip' => '192.168.1.1', 'browser' => 'Chrome 120'],
                'created_at' => Carbon::now()->subDay(),
            ],
            (object) [
                'id' => 7,
                'type' => 'system',
                'action' => 'error',
                'description' => 'Failed to sync inventory with external warehouse API',
                'user' => (object) ['name' => 'System', 'email' => 'system@internal'],
                'metadata' => (object) ['ip' => '127.0.0.1', 'browser' => 'N/A'],
                'created_at' => Carbon::now()->subDays(2),
            ],
            (object) [
                'id' => 8,
                'type' => 'payment',
                'action' => 'captured',
                'description' => 'Payment of $1,250.00 captured for order #ORD-2024-003',
                'user' => (object) ['name' => 'System', 'email' => 'system@internal'],
                'metadata' => (object) ['ip' => '127.0.0.1', 'browser' => 'N/A'],
                'created_at' => Carbon::now()->subDays(3),
            ],
        ];
    }

    public function clearLog(): void
    {
        session()->flash('success', 'Activity log has been cleared.');
    }

    public function exportLog(): void
    {
        session()->flash('success', 'Activity log export has been started. You will receive an email when ready.');
    }

    public function render()
    {
        $all = collect($this->getFakeActivities());

        $filtered = $all
            ->when($this->search, fn ($c) => $c->filter(fn ($a) =>
                str_contains(strtolower($a->description), strtolower($this->search)) ||
                str_contains(strtolower($a->user->name), strtolower($this->search))
            ))
            ->when($this->typeFilter !== 'all', fn ($c) => $c->where('type', $this->typeFilter));

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $activities = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.admin.activity-log', [
            'activities' => $activities,
        ]);
    }
}
