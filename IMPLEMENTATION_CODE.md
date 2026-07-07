# Remaining Laravel Livewire POS Files

The migrations and models are already in real Laravel paths. Add the following files to the same paths after scaffolding Laravel.

## `app/Notifications/SaleCompletedNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaleCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public Sale $sale)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->id,
            'invoice_no' => $this->sale->invoice_no,
            'total' => $this->sale->total,
            'payment_method' => $this->sale->payment_method,
            'cashier' => $this->sale->cashier?->name ?? 'Guest cashier',
            'message' => "New sale {$this->sale->invoice_no} completed.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New sale: {$this->sale->invoice_no}")
            ->line("A sale has been completed for {$this->sale->total}.")
            ->line("Payment method: {$this->sale->payment_method}")
            ->action('View sales report', url('/admin/reports/sales'));
    }
}
```

## `app/Livewire/PosTerminal.php`

```php
<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\SaleCompletedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class PosTerminal extends Component
{
    public string $barcode = '';
    public array $cart = [];
    public float $discount = 0;
    public float $tax = 0;
    public float $paidAmount = 0;
    public string $paymentMethod = 'cash';

    protected array $rules = [
        'paidAmount' => ['required', 'numeric', 'min:0'],
        'discount' => ['nullable', 'numeric', 'min:0'],
        'tax' => ['nullable', 'numeric', 'min:0'],
        'paymentMethod' => ['required', 'string', 'max:50'],
    ];

    public function addBarcode(): void
    {
        $barcode = trim($this->barcode);
        $this->barcode = '';

        if ($barcode === '') {
            return;
        }

        $product = Product::query()
            ->where('barcode', $barcode)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            $this->addError('barcode', 'Product not found for this barcode.');
            return;
        }

        $this->addProductToCart($product);
    }

    public function addProductToCart(Product $product): void
    {
        if ($product->stock < 1) {
            $this->addError('barcode', "{$product->name} is out of stock.");
            return;
        }

        if (isset($this->cart[$product->id])) {
            if ($this->cart[$product->id]['quantity'] >= $product->stock) {
                $this->addError('barcode', "Only {$product->stock} units available.");
                return;
            }

            $this->cart[$product->id]['quantity']++;
        } else {
            $this->cart[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => (float) $product->selling_price,
                'quantity' => 1,
                'stock' => $product->stock,
            ];
        }
    }

    public function increment(int $productId): void
    {
        if (isset($this->cart[$productId]) && $this->cart[$productId]['quantity'] < $this->cart[$productId]['stock']) {
            $this->cart[$productId]['quantity']++;
        }
    }

    public function decrement(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantity']--;

        if ($this->cart[$productId]['quantity'] <= 0) {
            unset($this->cart[$productId]);
        }
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['quantity']);
    }

    public function getTotalProperty(): float
    {
        return max(0, $this->subtotal - $this->discount + $this->tax);
    }

    public function getChangeAmountProperty(): float
    {
        return max(0, $this->paidAmount - $this->total);
    }

    public function checkout(): void
    {
        $this->validate();

        if (empty($this->cart)) {
            $this->addError('cart', 'Cart is empty.');
            return;
        }

        if ($this->paidAmount < $this->total) {
            $this->addError('paidAmount', 'Paid amount must cover the total.');
            return;
        }

        $sale = DB::transaction(function () {
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'invoice_no' => 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'tax' => $this->tax,
                'total' => $this->total,
                'paid_amount' => $this->paidAmount,
                'change_amount' => $this->changeAmount,
                'payment_method' => $this->paymentMethod,
            ]);

            foreach ($this->cart as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);

                if ($product->stock < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for {$product->name}.");
                }

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'barcode' => $product->barcode,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_total' => $item['price'] * $item['quantity'],
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            return $sale->load('cashier');
        });

        Notification::send(User::query()->where('is_admin', true)->get(), new SaleCompletedNotification($sale));

        $this->reset(['cart', 'discount', 'tax', 'paidAmount']);
        session()->flash('success', "Sale {$sale->invoice_no} completed.");
    }

    public function render()
    {
        return view('livewire.pos-terminal', [
            'products' => Product::query()->where('is_active', true)->orderBy('name')->limit(30)->get(),
        ]);
    }
}
```

## `app/Livewire/Admin/ProductManager.php`

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public string $barcode = '';
    public float $costPrice = 0;
    public float $sellingPrice = 0;
    public int $stock = 0;
    public int $lowStockAlert = 5;
    public bool $isActive = true;

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['required', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($this->editingId)],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'lowStockAlert' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        Product::updateOrCreate(['id' => $this->editingId], [
            'name' => $validated['name'],
            'barcode' => $validated['barcode'],
            'cost_price' => $validated['costPrice'],
            'selling_price' => $validated['sellingPrice'],
            'stock' => $validated['stock'],
            'low_stock_alert' => $validated['lowStockAlert'],
            'is_active' => $validated['isActive'],
        ]);

        $this->resetForm();
        session()->flash('success', 'Product saved.');
    }

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->barcode = $product->barcode;
        $this->costPrice = (float) $product->cost_price;
        $this->sellingPrice = (float) $product->selling_price;
        $this->stock = $product->stock;
        $this->lowStockAlert = $product->low_stock_alert;
        $this->isActive = $product->is_active;
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->barcode = '';
        $this->costPrice = 0;
        $this->sellingPrice = 0;
        $this->stock = 0;
        $this->lowStockAlert = 5;
        $this->isActive = true;
    }

    public function render()
    {
        return view('livewire.admin.product-manager', [
            'products' => Product::latest()->paginate(20),
        ]);
    }
}
```

## `app/Livewire/Admin/SalesReport.php`

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class SalesReport extends Component
{
    public string $fromDate;
    public string $toDate;

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    private function salesQuery(): Builder
    {
        return Sale::query()->whereBetween('created_at', [
            Carbon::parse($this->fromDate)->startOfDay(),
            Carbon::parse($this->toDate)->endOfDay(),
        ]);
    }

    public function render()
    {
        $range = [
            Carbon::parse($this->fromDate)->startOfDay(),
            Carbon::parse($this->toDate)->endOfDay(),
        ];

        return view('livewire.admin.sales-report', [
            'sales' => $this->salesQuery()->with('cashier')->latest()->limit(50)->get(),
            'topProducts' => SaleItem::query()
                ->selectRaw('product_id, product_name, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                ->whereHas('sale', fn (Builder $query) => $query->whereBetween('created_at', $range))
                ->groupBy('product_id', 'product_name')
                ->orderByDesc('sold_qty')
                ->limit(10)
                ->get(),
            'totalSales' => (clone $this->salesQuery())->sum('total'),
            'totalOrders' => (clone $this->salesQuery())->count(),
            'totalItems' => SaleItem::query()
                ->whereHas('sale', fn (Builder $query) => $query->whereBetween('created_at', $range))
                ->sum('quantity'),
        ]);
    }
}
```

## `app/Livewire/Admin/NotificationsPanel.php`

```php
<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsPanel extends Component
{
    public function markAsRead(string $id): void
    {
        Auth::user()->unreadNotifications()->where('id', $id)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.admin.notifications-panel', [
            'notifications' => Auth::user()->notifications()->latest()->limit(50)->get(),
        ]);
    }
}
```

## `app/Http/Middleware/EnsureUserIsAdmin.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin, 403);

        return $next($request);
    }
}
```

## `routes/web.php`

```php
use App\Livewire\Admin\NotificationsPanel;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\SalesReport;
use App\Livewire\PosTerminal;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/pos', PosTerminal::class)->name('pos');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/products', ProductManager::class)->name('products');
        Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
        Route::get('/notifications', NotificationsPanel::class)->name('notifications');
    });
});
```

Register the admin middleware in Laravel 11+ `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

For Laravel 10, register it in `app/Http/Kernel.php` instead.

## `resources/views/layouts/app.blade.php`

```blade
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'POS System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-900">
    <nav class="border-b bg-white px-6 py-3">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <a href="/pos" class="font-semibold">POS System</a>
            <div class="flex gap-4 text-sm">
                <a href="/pos">POS</a>
                <a href="/admin/products">Products</a>
                <a href="/admin/reports/sales">Reports</a>
                <a href="/admin/notifications">Notifications</a>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl p-6">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
```

## `resources/views/livewire/pos-terminal.blade.php`

```blade
<div class="grid gap-6 lg:grid-cols-[1fr_380px]">
    <section class="space-y-4">
        <div class="rounded bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Scan barcode</label>
            <input type="text" wire:model="barcode" wire:keydown.enter="addBarcode" autofocus
                class="mt-2 w-full rounded border px-3 py-3 text-lg"
                placeholder="Scan barcode or type and press Enter">
            @error('barcode') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @if (session('success')) <p class="mt-2 text-sm text-green-600">{{ session('success') }}</p> @endif
        </div>

        <div class="rounded bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-semibold">Quick Products</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <button type="button" wire:click="addProductToCart({{ $product->id }})"
                        class="rounded border p-3 text-left hover:bg-slate-50">
                        <span class="block font-medium">{{ $product->name }}</span>
                        <span class="text-sm text-slate-500">{{ $product->barcode }}</span>
                        <span class="block text-sm">Rs {{ number_format($product->selling_price, 2) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <aside class="rounded bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Cart</h2>
        @error('cart') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="space-y-3">
            @forelse ($cart as $item)
                <div class="border-b pb-3">
                    <div class="flex justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $item['name'] }}</p>
                            <p class="text-sm text-slate-500">Rs {{ number_format($item['price'], 2) }}</p>
                        </div>
                        <button wire:click="removeItem({{ $item['id'] }})" class="text-sm text-red-600">Remove</button>
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
                <p class="text-sm text-slate-500">Scan a product to start the sale.</p>
            @endforelse
        </div>

        <div class="mt-4 space-y-3 border-t pt-4">
            <div class="flex justify-between"><span>Subtotal</span><strong>Rs {{ number_format($this->subtotal, 2) }}</strong></div>
            <label class="block text-sm">Discount
                <input type="number" step="0.01" wire:model.live="discount" class="mt-1 w-full rounded border px-3 py-2">
            </label>
            <label class="block text-sm">Tax
                <input type="number" step="0.01" wire:model.live="tax" class="mt-1 w-full rounded border px-3 py-2">
            </label>
            <label class="block text-sm">Payment
                <select wire:model="paymentMethod" class="mt-1 w-full rounded border px-3 py-2">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="bank">Bank transfer</option>
                </select>
            </label>
            <label class="block text-sm">Paid Amount
                <input type="number" step="0.01" wire:model.live="paidAmount" class="mt-1 w-full rounded border px-3 py-2">
            </label>
            @error('paidAmount') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="flex justify-between text-lg"><span>Total</span><strong>Rs {{ number_format($this->total, 2) }}</strong></div>
            <div class="flex justify-between"><span>Change</span><strong>Rs {{ number_format($this->changeAmount, 2) }}</strong></div>
            <button wire:click="checkout" class="w-full rounded bg-slate-900 px-4 py-3 font-semibold text-white">Complete Sale</button>
        </div>
    </aside>
</div>
```

## Admin Blade Views

Create these files using the same pattern:

- `resources/views/livewire/admin/product-manager.blade.php`
- `resources/views/livewire/admin/sales-report.blade.php`
- `resources/views/livewire/admin/notifications-panel.blade.php`

The Livewire classes above already provide all required data. The views should render product CRUD, sales totals, top products, recent sales, and notification cards.
