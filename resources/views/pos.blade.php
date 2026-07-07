<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">POS Terminal</h2>
            @if (auth()->user()->is_admin)
                <div class="flex gap-4 text-sm">
                    <a class="text-indigo-600" href="{{ route('admin.products') }}">Products</a>
                    <a class="text-indigo-600" href="{{ route('admin.reports.sales') }}">Reports</a>
                    <a class="text-indigo-600" href="{{ route('admin.notifications') }}">Notifications</a>
                </div>
            @endif
        </div>
    </x-slot>

    <livewire:pos-terminal />
</x-app-layout>
