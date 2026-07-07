<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public string $barcode = '';
    public ?float $costPrice = null;
    public ?float $sellingPrice = null;
    public ?int $stock = null;
    public ?int $lowStockAlert = null;
    public bool $isActive = true;
    public ?string $currentImagePath = null;
    public int $fileInputKey = 0;
    public $image = null;

    public function save(): void
    {
        $wasEditing = (bool) $this->editingId;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['required', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($this->editingId)],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'lowStockAlert' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $imagePath = $this->currentImagePath;

        if ($this->image) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $this->image->store('products', 'public');
        }

        Product::updateOrCreate(['id' => $this->editingId], [
            'name' => $validated['name'],
            'barcode' => $validated['barcode'],
            'image_path' => $imagePath,
            'cost_price' => (float) $validated['costPrice'],
            'selling_price' => (float) $validated['sellingPrice'],
            'stock' => (int) $validated['stock'],
            'low_stock_alert' => (int) $validated['lowStockAlert'],
            'is_active' => (bool) $validated['isActive'],
        ]);

        $this->resetForm();
        $this->resetPage();

        session()->flash('success', $wasEditing ? 'Product updated.' : 'Product added.');
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
        $this->currentImagePath = $product->image_path;
        $this->image = null;
        $this->fileInputKey++;
        $this->resetErrorBag();

        $this->dispatch('product-editing');
    }

    public function removeImage(): void
    {
        if ($this->currentImagePath) {
            Storage::disk('public')->delete($this->currentImagePath);
        }

        if ($this->editingId) {
            Product::whereKey($this->editingId)->update(['image_path' => null]);
        }

        $this->currentImagePath = null;
        $this->image = null;
        $this->fileInputKey++;
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->barcode = '';
        $this->costPrice = null;
        $this->sellingPrice = null;
        $this->stock = null;
        $this->lowStockAlert = null;
        $this->isActive = true;
        $this->currentImagePath = null;
        $this->image = null;
        $this->fileInputKey++;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.product-manager', [
            'products' => Product::latest()->paginate(20),
        ]);
    }
}
