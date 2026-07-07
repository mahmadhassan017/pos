<div>
    <div class="py-6">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
            <form id="user-form" wire:key="user-form-{{ $editingId ?? 'new' }}" wire:submit.prevent="save" class="space-y-3 rounded bg-white p-4 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold">{{ $editingId ? 'Edit User' : 'Add User' }}</h3>
                    @if ($editingId)
                        <p class="mt-1 rounded bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
                            Editing user #{{ $editingId }}. Leave password blank to keep the current password.
                        </p>
                    @endif
                </div>

                @if (session('success')) <p class="text-sm text-green-600">{{ session('success') }}</p> @endif
                @if (session('error')) <p class="text-sm text-red-600">{{ session('error') }}</p> @endif

                <label class="block text-sm">Name
                    <input wire:model="name" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Email
                    <input type="email" wire:model="email" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Password
                    <input type="password" wire:model="password" class="mt-1 w-full rounded border-gray-300 shadow-sm" autocomplete="new-password">
                </label>
                @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Confirm Password
                    <input type="password" wire:model="password_confirmation" class="mt-1 w-full rounded border-gray-300 shadow-sm" autocomplete="new-password">
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="isAdmin"> Admin user
                </label>

                <div class="flex gap-2">
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-gray-900 px-4 py-2 text-white disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update User' : 'Add User' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                    <button type="button" wire:click="resetForm" class="rounded border px-4 py-2">{{ $editingId ? 'Cancel Edit' : 'Clear' }}</button>
                </div>
            </form>

            <section class="rounded bg-white p-4 shadow-sm">
                <h3 class="mb-3 text-lg font-semibold">Users</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr wire:key="user-row-{{ $user->id }}" class="border-b {{ $editingId === $user->id ? 'bg-indigo-50' : '' }}">
                                    <td class="py-2 font-medium">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="rounded px-2 py-1 text-xs {{ $user->is_admin ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $user->is_admin ? 'Admin' : 'Cashier' }}
                                        </span>
                                    </td>
                                    <td>{{ optional($user->created_at)->format('d M Y') }}</td>
                                    <td class="space-x-3 text-right">
                                        <button type="button" onclick="document.getElementById('user-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" wire:click="edit({{ $user->id }})" class="text-indigo-600">
                                            Edit
                                        </button>
                                        <button type="button" onclick="return confirm('Delete this user?')" wire:click="delete({{ $user->id }})" class="text-red-600">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $users->links() }}</div>
            </section>
        </div>
    </div>
</div>
