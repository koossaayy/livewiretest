<div class="max-w-7xl mx-auto py-8 px-4">
    {{ __(':param_1 total users', []) }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-1">
            <label for="user-search" class="sr-only">{{ __('Search users') }}</label>
            <input
                wire:model.live.debounce.300ms="search"
                id="user-search"
                type="search"
                placeholder="{{ __('Search by name or email...') }}"
                class="w-full rounded-lg border-gray-300 shadow-sm"
            />
        </div>
        <div>
            <label for="role-filter" class="sr-only">{{ __('Filter by role') }}</label>
            <select wire:model.live="roleFilter" id="role-filter" class="rounded-lg border-gray-300 shadow-sm">
                <option value="all">{{ __('All Roles') }}</option>
                <option value="admin">{{ __('Administrators') }}</option>
                <option value="manager">{{ __('Managers') }}</option>
                <option value="member">{{ __('Members') }}</option>
            </select>
        </div>
    </div>

    @php
        $roleLabels = [
            'admin' => __('Administrator'),
            'manager' => __('Manager'),
            'member' => __('Member'),
            'viewer' => __('View Only'),
        ];

        $statusBadgeClasses = [
            'active' => 'bg-green-100 text-green-800',
            'suspended' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
        ];
    @endphp

    {{-- Users Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('name')" class="hover:text-gray-900 flex items-center gap-1">
                            {{ __('Name') }}
                            @if ($sortField === 'name')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('email')" class="hover:text-gray-900 flex items-center gap-1">
                            {{ __('Email Address') }}
                            @if ($sortField === 'email')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('created_at')" class="hover:text-gray-900 flex items-center gap-1">
                            {{ __('Joined') }}
                            @if ($sortField === 'created_at')
                                <span class="text-blue-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-700">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $roleLabels[$user->role] ?? 'Unknown Role' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($user->suspended_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClasses['suspended'] }}">
                                    {{ __('Suspended') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClasses['active'] }}">
                                    {{ __('Active') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="/admin/users/{{ $user->id }}" wire:navigate class="text-sm text-blue-600 hover:underline">
                                {{ __('View Profile') }}
                            </a>
                            @if ($user->suspended_at)
                                <button
                                    wire:click="reactivateUser({{ $user->id }})"
                                    class="text-sm text-green-600 hover:underline"
                                >
                                    {{ __('Reactivate') }}
                                </button>
                            @else
                                <button
                                    wire:click="suspendUser({{ $user->id }})"
                                    wire:confirm="{{ __('Are you sure you want to suspend this user?') }}"
                                    class="text-sm text-yellow-600 hover:underline"
                                >
                                    {{ __('Suspend') }}
                                </button>
                            @endif
                            <button
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="{{ __('This will permanently delete the user and all their data. This cannot be undone. Continue?') }}"
                                class="text-sm text-red-600 hover:underline"
                            >
                                {{ __('Delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <p class="text-gray-500 text-lg">{{ __('No users found') }}</p>
                            <p class="text-gray-400 text-sm mt-1">{{ __('Try adjusting your search or filter criteria.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    @php
        $exportNote = __('Exported data includes name, email, role, and join date only.');
    @endphp

    <p class="text-sm text-gray-400 mt-4 text-center">
        {{ __('Showing :from to :to of :total users', ['from' => $users->firstItem() ?? 0, 'to' => $users->lastItem() ?? 0, 'total' => $users->total()]) }}
    </p>
</div>
