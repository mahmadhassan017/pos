<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Product Manager</h2>
            <a class="text-sm text-indigo-600" href="{{ route('pos') }}">Back to POS</a>
        </div>
    </x-slot>

    <livewire:admin.product-manager />
</x-app-layout>
