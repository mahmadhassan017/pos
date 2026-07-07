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
    public string $productSearch = '';
    public array $cart = [];
    public int $productLimit = 12;
    public float $discount = 0;
    public float $tax = 0;
    public float $paidAmount = 0;
    public string $paymentMethod = 'cash';
    public ?int $lastSaleId = null;

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

        $this->addProduct($product);
    }

    public function addProductToCart(int $productId): void
    {
        $product = Product::query()
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            $this->addError('barcode', 'Product not found.');
            return;
        }

        $this->addProduct($product);
    }

    private function addProduct(Product $product): void
    {
        $this->resetErrorBag();

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
                'image_url' => $product->image_url,
                'price' => (float) $product->selling_price,
                'quantity' => 1,
                'stock' => $product->stock,
            ];
        }
    }

    public function updatedProductSearch(): void
    {
        $this->productLimit = 12;
    }

    public function loadMoreProducts(): void
    {
        $this->productLimit += 12;
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

        Notification::send(
            User::query()->where('is_admin', true)->get(),
            new SaleCompletedNotification($sale)
        );

        $this->lastSaleId = $sale->id;
        $this->reset(['cart', 'discount', 'tax', 'paidAmount']);
        session()->flash('success', "Sale {$sale->invoice_no} completed.");
    }

    public function render()
    {
        $productsQuery = Product::query()
            ->where('is_active', true)
            ->when(trim($this->productSearch) !== '', function ($query) {
                $search = '%' . trim($this->productSearch) . '%';

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('barcode', 'like', $search);
                });
            })
            ->orderBy('name');

        $totalMatchingProducts = (clone $productsQuery)->count();

        return view('livewire.pos-terminal', [
            'hasMoreProducts' => $totalMatchingProducts > $this->productLimit,
            'products' => $productsQuery
                ->limit($this->productLimit)
                ->get(),
        ]);
    }
}
