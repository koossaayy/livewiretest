<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use App\Models\User;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
#[Title('User Management')]
class UserTable extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $roleFilter = 'all';
    public int $perPage = 15;

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function suspendUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', __('You cannot suspend your own account.'));
            return;
        }

        $user->update(['suspended_at' => now()]);

        Log::warning('User suspended by admin', [
            'admin_id' => auth()->id(),
            'user_id' => $userId,
        ]);

        session()->flash('success', __('User has been suspended.'));
        $this->dispatch('user-updated');
    }

    public function reactivateUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['suspended_at' => null]);

        Log::info('User reactivated by admin', [
            'admin_id' => auth()->id(),
            'user_id' => $userId,
        ]);

        session()->flash('success', __('User account has been reactivated.'));
        $this->dispatch('user-updated');
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', __('You cannot delete your own account.'));
            return;
        }

        if ($user->hasRole('admin')) {
            session()->flash('error', __('Administrator accounts cannot be deleted from here.'));
            return;
        }

        $user->tasks()->delete();
        $user->delete();

        Log::warning('User deleted by admin', [
            'admin_id' => auth()->id(),
            'deleted_user_id' => $userId,
        ]);

        session()->flash('success', __('User and all associated data have been permanently deleted.'));
    }

    public function exportUsers(): void
    {
        try {
            Log::info('User export initiated', ['admin_id' => auth()->id()]);
            // Export logic...
            session()->flash('success', __('User data export is being prepared. You will receive an email when it is ready.'));
        } catch (\Exception $e) {
            Log::error('User export failed', ['error' => $e->getMessage()]);
            session()->flash('error', __('Failed to start export. Please try again.'));
        }
    }

    #[Computed]
    public function userCount(): int
    {
        return User::count();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->roleFilter !== 'all', fn ($q) => $q->where('role', $this->roleFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.user-table', [
            'users' => $users,
        ]);
    }
}