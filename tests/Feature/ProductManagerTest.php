<?php

namespace Tests\Feature;

use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product_from_product_manager(): void
    {
        Livewire::test(ProductManager::class)
            ->set('name', 'Test Rice')
            ->set('barcode', 'TEST-RICE-001')
            ->set('costPrice', 120)
            ->set('sellingPrice', 150)
            ->set('stock', 25)
            ->set('lowStockAlert', 5)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Product added.');

        $this->assertDatabaseHas('products', [
            'name' => 'Test Rice',
            'barcode' => 'TEST-RICE-001',
            'selling_price' => 150,
            'stock' => 25,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_a_product_from_product_manager(): void
    {
        $product = Product::create([
            'name' => 'Old Sugar',
            'barcode' => 'OLD-SUGAR-001',
            'cost_price' => 90,
            'selling_price' => 110,
            'stock' => 10,
            'low_stock_alert' => 3,
            'is_active' => true,
        ]);

        Livewire::test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('name', 'Updated Sugar')
            ->set('sellingPrice', 125)
            ->set('stock', 18)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Product updated.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Sugar',
            'barcode' => 'OLD-SUGAR-001',
            'selling_price' => 125,
            'stock' => 18,
        ]);
    }
}
