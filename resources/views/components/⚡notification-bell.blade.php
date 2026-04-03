<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component
{
    public bool $dropdownOpen = false;

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()->notifications()->latest()->limit(5)->get();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        session()->flash('success', 'All notifications marked as read.');
    }

    #[On('task-saved')]
    #[On('user-updated')]
    public function refreshNotifications(): void
    {
        unset($this->unreadCount, $this->notifications);
    }
};
?>

<div class="relative" x-data="{ open: $wire.entangle('dropdownOpen') }">
    {{-- Bell Icon Button --}}
    <button
        x-on:click="open = !open"
        class="relative p-2 text-gray-600 hover:text-gray-900 rounded-full hover:bg-gray-100 transition"
        title="Notifications"
        aria-label="View notifications"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- Unread Badge --}}
        @if ($this->unreadCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-on:click.outside="open = false"
        x-transition
        class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50"
    >
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            @if ($this->unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-blue-600 hover:underline">
                    Mark all as read
                </button>
            @endif
        </div>

        <div class="max-h-64 overflow-y-auto">
            @forelse ($this->notifications as $notification)
                <div
                    wire:key="notification-{{ $notification->id }}"
                    class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 {{ $notification->read_at ? '' : 'bg-blue-50' }}"
                >
                    <p class="text-sm text-gray-800">{{ $notification->data['message'] ?? __('New notification') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    @if (! $notification->read_at)
                        <button
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="text-xs text-blue-600 hover:underline mt-1"
                        >
                            Mark as read
                        </button>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-2.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 007.586 13H5" />
                    </svg>
                    <p class="text-sm text-gray-500">No new notifications</p>
                    <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
                </div>
            @endforelse
        </div>

        @if ($this->notifications->isNotEmpty())
            <div class="px-4 py-3 border-t border-gray-200 text-center">
                <a href="/notifications" wire:navigate class="text-sm text-blue-600 hover:underline">
                    View All Notifications
                </a>
            </div>
        @endif
    </div>
</div>
