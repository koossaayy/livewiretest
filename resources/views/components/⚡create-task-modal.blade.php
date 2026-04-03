<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Locked;
use App\Models\Task;

new class extends Component
{
    public bool $showModal = false;

    #[Validate('required|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'medium';

    #[Validate('nullable|date|after:today')]
    public string $dueDate = '';

    #[Validate('nullable|exists:users,id')]
    public ?int $assigneeId = null;

    #[Locked]
    public ?int $editingTaskId = null;

    public string $formError = '';

    #[On('open-modal')]
    public function openModal(?int $taskId = null): void
    {
        $this->resetValidation();
        $this->formError = '';

        if ($taskId) {
            $task = Task::findOrFail($taskId);
            $this->editingTaskId = $taskId;
            $this->title = $task->title;
            $this->description = $task->description ?? '';
            $this->priority = $task->priority_label;
            $this->dueDate = $task->due_date?->format('Y-m-d') ?? '';
            $this->assigneeId = $task->assignee_id;
        } else {
            $this->reset(['title', 'description', 'priority', 'dueDate', 'assigneeId', 'editingTaskId']);
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
                'due_date' => $this->dueDate ?: null,
                'assignee_id' => $this->assigneeId,
            ];

            if ($this->editingTaskId) {
                $task = Task::findOrFail($this->editingTaskId);
                $task->update($data);

                $this->dispatch('notify', message: 'Task updated successfully!', type: 'success');
                session()->flash('success', 'Your changes have been saved.');
            } else {
                $data['user_id'] = auth()->id();
                $data['status'] = 'pending';
                Task::create($data);

                $this->dispatch('notify', message: 'Task created!', type: 'success');
                session()->flash('success', 'New task has been created.');
            }

            $this->closeModal();
            $this->dispatch('task-saved');
        } catch (\Exception $e) {
            $this->formError = 'An unexpected error occurred. Please try again.';
            $this->dispatch('notify', message: 'Something went wrong. Please try again.', type: 'error');
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['title', 'description', 'priority', 'dueDate', 'assigneeId', 'editingTaskId', 'formError']);
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'users' => \App\Models\User::select('id', 'name')->orderBy('name')->get(),
        ];
    }
};
?>

<div>
    @if ($showModal)
        <div
            x-data="{ closing: false }"
            x-on:keydown.escape.window="$wire.closeModal()"
            class="fixed inset-0 z-50 flex items-center justify-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 transition-opacity" x-on:click="$wire.closeModal()"></div>

            {{-- Modal Panel --}}
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 id="modal-title" class="text-xl font-semibold text-gray-900">
                        @if ($editingTaskId)
                            Edit Task
                        @else
                            Create New Task
                        @endif
                    </h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600" aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($formError)
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4" role="alert">
                        {{ $formError }}
                    </div>
                @endif

                <form wire:submit="save" class="space-y-4">
                    {{-- Title --}}
                    <div>
                        <label for="task-title" class="block text-sm font-medium text-gray-700 mb-1">
                            Task Title
                        </label>
                        <input
                            wire:model="title"
                            id="task-title"
                            type="text"
                            placeholder="Enter a descriptive task title"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        />
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="task-description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            wire:model="description"
                            id="task-description"
                            rows="3"
                            placeholder="Add details about this task..."
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Optional. Provide additional context for this task.</p>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label for="task-priority" class="block text-sm font-medium text-gray-700 mb-1">
                            Priority Level
                        </label>
                        <select
                            wire:model="priority"
                            id="task-priority"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label for="task-due-date" class="block text-sm font-medium text-gray-700 mb-1">
                            Due Date
                        </label>
                        <input
                            wire:model="dueDate"
                            id="task-due-date"
                            type="date"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">Leave blank if there is no deadline.</p>
                        @error('dueDate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Assignee --}}
                    <div>
                        <label for="task-assignee" class="block text-sm font-medium text-gray-700 mb-1">
                            Assign To
                        </label>
                        <select
                            wire:model="assigneeId"
                            id="task-assignee"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Unassigned</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('assigneeId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                        >
                            @if ($editingTaskId)
                                Save Changes
                            @else
                                Create Task
                            @endif
                        </button>
                    </div>
                </form>

                <p class="text-xs text-gray-400 mt-3 text-center">
                    {{ __('All fields marked are required.') }}
                </p>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (params) => {
                const defaults = {
                    duration: 3000,
                    position: 'top-right',
                };
                const message = params.message || 'Action completed';
                const type = params.type || 'info';
                const title = type === 'error' ? 'Error' : 'Success';

                if (window.toastNotification) {
                    window.toastNotification.show({
                        title: title,
                        message: message,
                        type: type,
                        ...defaults,
                    });
                }
            });
        });
    </script>
</div>
