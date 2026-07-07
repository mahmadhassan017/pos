<div>
    <div class="py-6">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
            <form id="product-form" wire:key="product-form-{{ $editingId ?? 'new' }}-{{ $fileInputKey }}" wire:submit.prevent="save" class="space-y-3 rounded bg-white p-4 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold">{{ $editingId ? 'Edit Product' : 'Add Product' }}</h3>
                    @if ($editingId)
                        <p class="mt-1 rounded bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
                            Editing product #{{ $editingId }}. Update the fields below and click Update Product.
                        </p>
                    @endif
                </div>
                @if (session('success')) <p class="text-sm text-green-600">{{ session('success') }}</p> @endif

                <label class="block text-sm">Name
                    <input wire:model="name" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Barcode
                    <input wire:model="barcode" class="mt-1 w-full rounded border-gray-300 shadow-sm" placeholder="Scan barcode here">
                </label>
                @error('barcode') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Product Image
                    <input type="file" wire:model="image" wire:key="product-image-{{ $fileInputKey }}" accept="image/*" class="mt-1 w-full text-sm">
                </label>
                @error('image') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="h-16 w-16 rounded object-cover">
                @elseif ($currentImagePath)
                    <div class="flex items-center gap-3">
                        <img src="{{ Storage::url($currentImagePath) }}" alt="Current product image" class="h-16 w-16 rounded object-cover">
                        <button type="button" wire:click="removeImage" class="text-sm text-red-600">Remove image</button>
                    </div>
                @endif

                <label class="block text-sm">Cost Price
                    <input type="number" step="0.01" wire:model="costPrice" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('costPrice') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Selling Price
                    <input type="number" step="0.01" wire:model="sellingPrice" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('sellingPrice') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Stock
                    <input type="number" wire:model="stock" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('stock') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="block text-sm">Low Stock Alert
                    <input type="number" wire:model="lowStockAlert" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                </label>
                @error('lowStockAlert') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="isActive"> Active
                </label>

                <div class="flex gap-2">
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-gray-900 px-4 py-2 text-white disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update Product' : 'Add Product' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                    <button type="button" wire:click="resetForm" class="rounded border px-4 py-2">{{ $editingId ? 'Cancel Edit' : 'Clear' }}</button>
                </div>
            </form>

            <section class="rounded bg-white p-4 shadow-sm">
                <h3 class="mb-3 text-lg font-semibold">Products</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="w-10 py-2">Image</th>
                                <th class="py-2">Name</th>
                                <th>Barcode</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr wire:key="product-row-{{ $product->id }}" class="border-b {{ $editingId === $product->id ? 'bg-indigo-50' : '' }}">
                                    <td class="w-10 py-2">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-6 w-6 max-w-none shrink-0 rounded object-cover">
                                        @else
                                            <div class="flex h-6 w-6 max-w-none shrink-0 items-center justify-center rounded bg-gray-100 text-[8px] text-gray-400">No</div>
                                        @endif
                                    </td>
                                    <td class="py-2">{{ $product->name }}</td>
                                    <td>{{ $product->barcode }}</td>
                                    <td>Rs {{ number_format($product->selling_price, 2) }}</td>
                                    <td class="{{ $product->isLowStock() ? 'text-red-600' : '' }}">{{ $product->stock }}</td>
                                    <td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <button type="button" onclick="document.getElementById('product-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" wire:click="edit({{ $product->id }})" wire:loading.attr="disabled" wire:target="edit" class="text-indigo-600 disabled:opacity-50">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $products->links() }}</div>
            </section>
        </div>
    </div>
</div>
