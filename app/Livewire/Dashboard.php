<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $period = 'week';
    public string $errorMessage = '';

    #[Computed]
    public function stats(): array
    {
        $user = Auth::user();
        $dateThreshold = match ($this->period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        return [
            'total' => $user->tasks()->where('created_at', '>=', $dateThreshold)->count(),
            'completed' => $user->tasks()->where('status', 'completed')->where('updated_at', '>=', $dateThreshold)->count(),
            'overdue' => $user->tasks()->where('status', 'pending')->where('due_date', '<', now())->count(),
            'in_progress' => $user->tasks()->where('status', 'in_progress')->count(),
        ];
    }

    #[Computed]
    public function recentTasks(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->tasks()
            ->latest()
            ->limit(5)
            ->get();
    }

    public function markComplete(int $taskId): void
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($taskId);
        $task->update(['status' => 'completed']);

        session()->flash('success', 'Task completed! Great job.');
    }

    public function archiveCompleted(): void
    {
        $count = Auth::user()->tasks()
            ->where('status', 'completed')
            ->update(['archived' => true]);

        if ($count > 0) {
            session()->flash('success', "{$count} completed tasks have been archived.");
        } else {
            session()->flash('info', 'No completed tasks to archive.');
        }
    }

    public function exportTasks(): void
    {
        try {
            // Export logic...
            session()->flash('success', 'Your tasks have been exported successfully.');
        } catch (\Exception $e) {
            $this->errorMessage = 'Unable to export tasks at this time.';
            session()->flash('error', 'Export failed. Please try again later.');
        }
    }

    public function render()
    {
        return <<<'HTML'
        <div class="max-w-7xl mx-auto py-8 px-4">
            {{-- Flash Messages --}}
            @if (session()->has('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('info'))
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6" role="alert">
                    {{ session('info') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Dashboard Header --}}
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ __('Dashboard') }}</h1>
                    <p class="text-gray-600 mt-1">{{ __("Here's what's happening with your tasks.") }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <select wire:model.live="period" class="rounded-lg border-gray-300 shadow-sm text-sm">
                        <option value="today">{{ __('Today') }}</option>
                        <option value="week">{{ __('This Week') }}</option>
                        <option value="month">{{ __('This Month') }}</option>
                    </select>
                    <button
                        wire:click="exportTasks"
                        class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm font-medium"
                    >
                        {{ __('Export Tasks') }}
                    </button>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Total Tasks') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $this->stats['total'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Completed') }}</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $this->stats['completed'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('In Progress') }}</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $this->stats['in_progress'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Overdue') }}</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ $this->stats['overdue'] }}</p>
                </div>
            </div>

            {{-- Admin Section --}}
            @can('manage-tasks')
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                    <h2 class="text-lg font-semibold text-yellow-800 mb-2">{{ __('Administration') }}</h2>
                    <p class="text-sm text-yellow-700 mb-4">{{ __('You have admin privileges. You can manage all user tasks from here.') }}</p>
                    <div class="flex gap-3">
                        <a href="/admin/users" wire:navigate class="text-sm font-medium text-yellow-800 hover:underline">
                            Manage Users
                        </a>
                        <a href="/admin/reports" wire:navigate class="text-sm font-medium text-yellow-800 hover:underline">
                            {{ __('View Reports') }}
                        </a>
                    </div>
                </div>
            @endcan

            {{-- Recent Tasks Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Recent Tasks') }}</h2>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">{{ __('Showing latest activity') }}</span>
                        <button
                            wire:click="archiveCompleted"
                            wire:confirm="{{ __('Are you sure you want to archive all completed tasks?') }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            {{ __('Archive Completed') }}
                        </button>
                    </div>
                </div>

                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Task') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Due Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Priority') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($this->recentTasks as $task)
                            <tr wire:key="dashboard-task-{{ $task->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $task->title }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $task->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $task->due_date?->format('M d, Y') ?? 'No deadline' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ ucfirst($task->priority_label ?? 'Normal') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if ($task->status !== 'completed')
                                        <button
                                            wire:click="markComplete({{ $task->id }})"
                                            class="text-sm text-green-600 hover:underline"
                                        >
                                            {{ __('Mark Complete') }}
                                        </button>
                                    @else
                                        <span class="text-sm text-gray-400">{{ __('Done') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <p class="text-gray-500 text-lg">{{ __('No tasks yet') }}</p>
                                    <p class="text-gray-400 text-sm mt-1">{{ __('Create your first task to get started.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($errorMessage)
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ $errorMessage }}
                </div>
            @endif
        </div>
        HTML;
    }
}
