<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifications</h2>
            <a class="text-sm text-indigo-600" href="{{ route('pos') }}">Back to POS</a>
        </div>
    </x-slot>

    <div class="py-6">
        <section class="mx-auto max-w-4xl rounded bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Sale Notifications</h3>
                <button wire:click="markAllAsRead" class="rounded border px-3 py-2 text-sm">Mark all as read</button>
            </div>

            <div class="space-y-3">
                @forelse ($notifications as $notification)
                    <div class="rounded border p-3 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ $notification->data['message'] ?? 'New notification' }}</p>
                                <p class="text-sm text-gray-600">
                                    Invoice {{ $notification->data['invoice_no'] ?? '-' }}
                                    - Rs {{ number_format($notification->data['total'] ?? 0, 2) }}
                                    - {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if (! $notification->read_at)
                                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-sm text-indigo-600">Mark read</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No notifications yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
