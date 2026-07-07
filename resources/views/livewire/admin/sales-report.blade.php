<div>
    <div class="py-6">
        <div class="mx-auto space-y-6 px-4 sm:px-6 lg:max-w-7xl lg:px-8">
            <section class="rounded bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <label class="text-sm">From
                        <input type="date" wire:model.live="fromDate" class="mt-1 rounded border-gray-300 shadow-sm">
                    </label>
                    <label class="text-sm">To
                        <input type="date" wire:model.live="toDate" class="mt-1 rounded border-gray-300 shadow-sm">
                    </label>
                    <button wire:click="exportInvoices" class="rounded border px-3 py-2 text-sm font-medium hover:bg-gray-50">
                        Export invoices list
                    </button>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Total Revenue</p>
                    <strong class="text-2xl">Rs {{ number_format($totalSales, 2) }}</strong>
                </div>
                <div class="rounded bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Invoices</p>
                    <strong class="text-2xl">{{ $totalOrders }}</strong>
                </div>
                <div class="rounded bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Items Sold</p>
                    <strong class="text-2xl">{{ $totalItems }}</strong>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                @foreach ([
                    'daily' => ['title' => 'Daily Revenue', 'rows' => $dailyRevenue],
                    'weekly' => ['title' => 'Weekly Revenue', 'rows' => $weeklyRevenue],
                    'monthly' => ['title' => 'Monthly Revenue', 'rows' => $monthlyRevenue],
                ] as $type => $report)
                    <div class="rounded bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="font-semibold">{{ $report['title'] }}</h3>
                            <button wire:click="exportRevenue('{{ $type }}')" class="rounded border px-3 py-1.5 text-xs font-medium hover:bg-gray-50">
                                Export
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b text-gray-500">
                                        <th class="py-2">Period</th>
                                        <th>Invoices</th>
                                        <th class="text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['rows'] as $row)
                                        <tr class="border-b">
                                            <td class="py-2">{{ $row['label'] }}</td>
                                            <td>{{ $row['orders'] }}</td>
                                            <td class="text-right">
                                                <button wire:click="openInvoices('{{ $type }}', '{{ $row['key'] }}')" class="font-semibold text-indigo-600 hover:underline">
                                                    Rs {{ number_format($row['revenue'], 2) }}
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="py-3 text-gray-500" colspan="3">No sales found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="rounded bg-white p-4 shadow-sm">
                <h3 class="mb-3 font-semibold">Top Products</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b"><th class="py-2">Product</th><th>Qty</th><th class="text-right">Revenue</th></tr></thead>
                        <tbody>
                            @forelse ($topProducts as $product)
                                <tr class="border-b">
                                    <td class="py-2">{{ $product->product_name }}</td>
                                    <td>{{ $product->sold_qty }}</td>
                                    <td class="text-right">Rs {{ number_format($product->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-3 text-gray-500" colspan="3">No product sales yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @if ($selectedType && $selectedKey)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
            <div class="max-h-[90vh] w-full max-w-6xl overflow-hidden rounded bg-white shadow-xl">
                <div class="flex items-center justify-between border-b p-4">
                    <div>
                        <h3 class="text-lg font-semibold">Invoices for {{ $selectedLabel }}</h3>
                        <p class="text-sm text-gray-500">{{ ucfirst($selectedType) }} report drill-down</p>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="exportInvoices('{{ $selectedType }}', '{{ $selectedKey }}')" class="rounded border px-3 py-2 text-sm hover:bg-gray-50">
                            Export invoices
                        </button>
                        <button wire:click="closeInvoices" class="rounded bg-gray-900 px-3 py-2 text-sm text-white">Close</button>
                    </div>
                </div>

                <div class="grid max-h-[75vh] overflow-y-auto lg:grid-cols-[1fr_420px]">
                    <div class="border-r p-4">
                        <table class="w-full text-left text-sm">
                            <thead><tr class="border-b text-gray-500"><th class="py-2">Invoice</th><th>Date</th><th>Cashier</th><th class="text-right">Total</th></tr></thead>
                            <tbody>
                                @forelse ($selectedInvoices as $invoice)
                                    <tr class="border-b">
                                        <td class="py-2">
                                            <button wire:click="showInvoice({{ $invoice->id }})" class="font-medium text-indigo-600 hover:underline">
                                                {{ $invoice->invoice_no }}
                                            </button>
                                        </td>
                                        <td>{{ $invoice->created_at->format('d M Y H:i') }}</td>
                                        <td>{{ $invoice->cashier?->name ?? 'Guest' }}</td>
                                        <td class="text-right">Rs {{ number_format($invoice->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="py-3 text-gray-500" colspan="4">No invoices for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4">
                        @if ($selectedSale)
                            <h4 class="font-semibold">{{ $selectedSale->invoice_no }}</h4>
                            <p class="mb-3 text-sm text-gray-500">
                                {{ $selectedSale->created_at->format('d M Y H:i') }}
                                - {{ $selectedSale->cashier?->name ?? 'Guest' }}
                            </p>

                            <table class="mb-4 w-full text-left text-sm">
                                <thead><tr class="border-b"><th class="py-2">Item</th><th>Qty</th><th class="text-right">Total</th></tr></thead>
                                <tbody>
                                    @foreach ($selectedSale->items as $item)
                                        <tr class="border-b">
                                            <td class="py-2">
                                                {{ $item->product_name }}<br>
                                                <span class="text-xs text-gray-500">{{ $item->barcode }} x Rs {{ number_format($item->unit_price, 2) }}</span>
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td class="text-right">Rs {{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between"><span>Subtotal</span><strong>Rs {{ number_format($selectedSale->subtotal, 2) }}</strong></div>
                                <div class="flex justify-between"><span>Discount</span><strong>Rs {{ number_format($selectedSale->discount, 2) }}</strong></div>
                                <div class="flex justify-between"><span>Tax</span><strong>Rs {{ number_format($selectedSale->tax, 2) }}</strong></div>
                                <div class="flex justify-between text-base"><span>Total</span><strong>Rs {{ number_format($selectedSale->total, 2) }}</strong></div>
                                <div class="flex justify-between"><span>Paid</span><strong>Rs {{ number_format($selectedSale->paid_amount, 2) }}</strong></div>
                                <div class="flex justify-between"><span>Change</span><strong>Rs {{ number_format($selectedSale->change_amount, 2) }}</strong></div>
                            </div>

                            <a href="{{ route('sales.receipt', $selectedSale) }}" target="_blank" class="mt-4 inline-block rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white">
                                Open receipt
                            </a>
                        @else
                            <p class="text-sm text-gray-500">Click an invoice to view its details.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
