<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Url;
use App\Models\Task;

new
#[Layout('layouts.app')]
#[Title('My Tasks')]
class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $filterStatus = 'all';
    public string $errorMessage = '';

    #[Validate('required|min:3|max:255')]
    public string $quickTaskTitle = '';

    public function addQuickTask(): void
    {
        $this->validate();

        $task = auth()->user()->tasks()->create([
            'title' => $this->quickTaskTitle,
            'status' => 'pending',
        ]);

        if (! $task) {
            $this->errorMessage = __('Something went wrong while creating the task.');
            session()->flash('error', __('Failed to create task. Please try again.'));
            return;
        }

        $this->quickTaskTitle = '';
        session()->flash('success', __('Task added successfully!'));
    }

    public function toggleComplete(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        if ($task->user_id !== auth()->id()) {
            $this->errorMessage = __('You do not have permission to modify this task.');
            return;
        }

        $task->update([
            'status' => $task->status === 'completed' ? 'pending' : 'completed',
        ]);

        $statusLabel = $task->status === 'completed' ? 'completed' : 'reopened';
        session()->flash('success', __('Task marked as :statusLabel.', ['statusLabel' => $statusLabel]));
    }

    public function deleteTask(int $taskId): void
    {
        $task = Task::where('user_id', auth()->id())->findOrFail($taskId);
        $task->delete();

        session()->flash('success', __('Task deleted permanently.'));
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

    public function with(): array
    {
        $query = auth()->user()->tasks()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus));

        $totalCount = auth()->user()->tasks()->count();
        $completedCount = auth()->user()->tasks()->where('status', 'completed')->count();
        $overdueCount = auth()->user()->tasks()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        $urgentCount = auth()->user()->tasks()
            ->where('priority', '>=', 3)
            ->where('status', '!=', 'completed')
            ->count();

        return [
            'tasks' => $query->orderBy($this->sortField, $this->sortDirection)->paginate(10),
            'totalCount' => $totalCount,
            'completedCount' => $completedCount,
            'overdueCount' => $overdueCount,
            'urgentCount' => $urgentCount,
        ];
    }
};?>

<div class="max-w-4xl mx-auto py-8">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Task Manager') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Welcome back,') }} {{ auth()->user()->name }}{{ __('! Here are your tasks.') }}</p>
        </div>
        <a href="/tasks/archive" wire:navigate class="text-blue-600 hover:underline">
            {{ __('View Archived Tasks') }}
        </a>
    </div>

    {{-- Stats Overview --}}
    @php
        $statusLabels = [
            'total' => __('Total Tasks'),
            'completed' => __('Completed'),
            'overdue' => __('Overdue'),
            'urgent' => __('Needs Attention'),
        ];
        $motivationalMessage = __('Keep up the great work!');
        if ($overdueCount > 0) {
            $motivationalMessage = __('You have overdue tasks that need your attention.');
        }@endphp

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ $statusLabels['total'] }}</span>
            <p class="text-2xl font-bold">{{ $totalCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ $statusLabels['completed'] }}</span>
            <p class="text-2xl font-bold text-green-600">{{ $completedCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ $statusLabels['overdue'] }}</span>
            <p class="text-2xl font-bold text-red-600">{{ $overdueCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <span class="text-sm text-gray-500">{{ $statusLabels['urgent'] }}</span>
            <p class="text-2xl font-bold text-orange-600">{{ $urgentCount }}</p>
        </div>
    </div>

    <p class="text-sm text-gray-500 mb-4 italic">{{ $motivationalMessage }}</p>

    {{-- Quick Add Form --}}
    <form wire:submit="addQuickTask" class="flex gap-2 mb-6">
        <input
            wire:model="quickTaskTitle"
            type="text"
            placeholder="{{ __('Add a new task...') }}"
            class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500"
        />
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            {{ __('Add Task') }}
        </button>
    </form>

    @error('quickTaskTitle')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror

    {{-- Filters --}}
    <div class="flex items-center gap-4 mb-4">
        <label for="search" class="text-sm font-medium text-gray-700">{{ __('Search tasks') }}</label>
        <input
            wire:model.live.debounce.300ms="search"
            id="search"
            type="search"
            placeholder="{{ __('Filter by title...') }}"
            class="rounded-lg border-gray-300 shadow-sm"
        />

        <label for="status-filter" class="text-sm font-medium text-gray-700">{{ __('Status') }}</label>
        <select wire:model.live="filterStatus" id="status-filter" class="rounded-lg border-gray-300 shadow-sm">
            <option value="all">{{ __('All Tasks') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
            <option value="in_progress">{{ __('In Progress') }}</option>
            <option value="completed">{{ __('Completed') }}</option>
        </select>
    </div>

    {{-- Task List --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('Your Tasks') }}</h2>
            <span class="text-sm text-gray-500">{{ __('Showing results') }}</span>
        </div>

        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">
                        <button wire:click="sortBy('title')" class="hover:text-gray-900">
                            {{ __('Task Name') }}
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">{{ __('Priority') }}</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">
                        <button wire:click="sortBy('due_date')" class="hover:text-gray-900">
                            {{ __('Due Date') }}
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr wire:key="task-{{ $task->id }}" class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="{{ $task->status === 'completed' ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                {{ $task->title }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($task->priority >= 3)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ __('High Priority') }}
                                </span>
                            @elseif ($task->priority >= 2)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ __('Medium Priority') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ __('Low Priority') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if ($task->due_date)
                                @if ($task->due_date < now() && $task->status !== 'completed')
                                    <span class="text-red-600 font-medium">{{ __('Overdue:') }} {{ $task->due_date->diffForHumans() }}</span>
                                @else
                                    {{ $task->due_date->format('M d, Y') }}
                                @endif
                            @else
                                {{ __('No due date') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ ucfirst($task->status) }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button
                                wire:click="toggleComplete({{ $task->id }})"
                                class="text-sm text-blue-600 hover:underline"
                            >
                                @if ($task->status === 'completed')
                                    {{ __('Reopen') }}
                                @else
                                    {{ __('Complete') }}
                                @endif
                            </button>
                            <button
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to delete this task?"
                                class="text-sm text-red-600 hover:underline"
                            >
                                {{ __('Delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <p class="text-lg font-medium">{{ __('No tasks found') }}</p>
                            <p class="text-sm mt-1">{{ __('Get started by adding your first task above.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($tasks->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>

    {{-- Footer Help Text --}}
    <p class="text-sm text-gray-400 mt-4 text-center">
        {{ __('Need help? Visit our support center.') }}
    </p>
</div>
