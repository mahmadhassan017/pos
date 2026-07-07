<div>
    <div class="py-6">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_380px] lg:px-8">
            <section class="space-y-4">
                <div class="rounded bg-white p-4 shadow-sm">
                    <label class="text-sm font-medium">Scan barcode</label>
                    <input type="text" wire:model="barcode" wire:keydown.enter="addBarcode" autofocus
                        class="mt-2 w-full rounded border-gray-300 px-3 py-3 text-lg shadow-sm"
                        placeholder="Scan barcode or type and press Enter">
                    @error('barcode') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if (session('success')) <p class="mt-2 text-sm text-green-600">{{ session('success') }}</p> @endif
                    @if ($lastSaleId)
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('sales.receipt', ['sale' => $lastSaleId, 'print' => 1]) }}" target="_blank"
                                class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white">
                                Print receipt
                            </a>
                            <a href="{{ route('sales.receipt.download', $lastSaleId) }}"
                                class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Download receipt
                            </a>
                        </div>
                    @endif
                </div>

                <div class="rounded bg-white p-4 shadow-sm">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="font-semibold">Quick Products</h3>
                        <input type="search" wire:model.live.debounce.300ms="productSearch"
                            class="w-full rounded border-gray-300 shadow-sm sm:w-72"
                            placeholder="Search name or barcode">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($products as $product)
                            <button type="button" wire:click="addProductToCart({{ $product->id }})"
                                class="min-h-[92px] overflow-hidden rounded border p-3 text-left transition hover:border-indigo-400 hover:bg-indigo-50">
                                <div class="flex items-start gap-3">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-w-none shrink-0 rounded object-cover" style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; object-fit: cover;">
                                    @else
                                        <div class="flex max-w-none shrink-0 items-center justify-center rounded bg-gray-100 text-[10px] text-gray-400" style="width: 48px; height: 48px; min-width: 48px; max-width: 48px;">No img</div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <span class="block truncate font-medium">{{ $product->name }}</span>
                                        <span class="block truncate text-sm text-gray-500">{{ $product->barcode }}</span>
                                        <span class="block text-sm">Rs {{ number_format($product->selling_price, 2) }}</span>
                                        <span class="block text-xs text-gray-500">Stock: {{ $product->stock }}</span>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <p class="text-sm text-gray-500">
                                No products found{{ $productSearch ? ' for "' . $productSearch . '"' : '' }}.
                            </p>
                        @endforelse
                    </div>

                    @if ($hasMoreProducts)
                        <div class="mt-4 text-center">
                            <button type="button" wire:click="loadMoreProducts"
                                class="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                View more products
                            </button>
                        </div>
                    @endif
                </div>
            </section>

            <aside class="rounded bg-white p-4 shadow-sm">
                <h3 class="mb-3 font-semibold">Cart</h3>
                @error('cart') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @forelse ($cart as $item)
                        <div class="border-b pb-3">
                            <div class="flex justify-between gap-3">
                                <div class="flex min-w-0 flex-1 items-start gap-3">
                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="max-w-none shrink-0 rounded object-cover" style="width: 40px; height: 40px; min-width: 40px; max-width: 40px; object-fit: cover;">
                                    @else
                                        <div class="flex max-w-none shrink-0 items-center justify-center rounded bg-gray-100 text-[9px] text-gray-400" style="width: 40px; height: 40px; min-width: 40px; max-width: 40px;">No img</div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium">{{ $item['name'] }}</p>
                                        <p class="text-sm text-gray-500">Rs {{ number_format($item['price'], 2) }}</p>
                                    </div>
                                </div>
                                <button wire:click="removeItem({{ $item['id'] }})" class="shrink-0 text-sm text-red-600">Remove</button>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button wire:click="decrement({{ $item['id'] }})" class="rounded border px-3 py-1">-</button>
                                    <span>{{ $item['quantity'] }}</span>
                                    <button wire:click="increment({{ $item['id'] }})" class="rounded border px-3 py-1">+</button>
                                </div>
                                <strong>Rs {{ number_format($item['price'] * $item['quantity'], 2) }}</strong>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Scan a product to start the sale.</p>
                    @endforelse
                </div>

                <div class="mt-4 space-y-3 border-t pt-4">
                    <div class="flex justify-between"><span>Subtotal</span><strong>Rs {{ number_format($this->subtotal, 2) }}</strong></div>
                    <label class="block text-sm">Discount
                        <input type="number" step="0.01" wire:model.live="discount" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                    </label>
                    <label class="block text-sm">Tax
                        <input type="number" step="0.01" wire:model.live="tax" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                    </label>
                    <label class="block text-sm">Payment
                        <select wire:model="paymentMethod" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank">Bank transfer</option>
                        </select>
                    </label>
                    <label class="block text-sm">Paid Amount
                        <input type="number" step="0.01" wire:model.live="paidAmount" class="mt-1 w-full rounded border-gray-300 shadow-sm">
                    </label>
                    @error('paidAmount') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="flex justify-between text-lg"><span>Total</span><strong>Rs {{ number_format($this->total, 2) }}</strong></div>
                    <div class="flex justify-between"><span>Change</span><strong>Rs {{ number_format($this->changeAmount, 2) }}</strong></div>
                    <button wire:click="checkout" class="w-full rounded bg-gray-900 px-4 py-3 font-semibold text-white">Complete Sale</button>
                </div>
            </aside>
        </div>
    </div>
</div>
