<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPasswordHash = '$2y$10$CZAgIzS4YzhagO7iNWA25OWraIlLYLdGkdHtEimaBZci7NEh1XGLO';

        DB::table('users')->updateOrInsert([
            'email' => 'admin@pos.test',
        ], [
            'name' => 'POS Admin',
            'password' => $defaultPasswordHash,
            'is_admin' => true,
            'email_verified_at' => now(),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        DB::table('users')->updateOrInsert([
            'email' => 'cashier@pos.test',
        ], [
            'name' => 'POS Cashier',
            'password' => $defaultPasswordHash,
            'is_admin' => false,
            'email_verified_at' => now(),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        $products = [
            ['name' => 'Demo Milk 1L', 'barcode' => '1000001', 'cost_price' => 180, 'selling_price' => 220, 'stock' => 50],
            ['name' => 'Demo Bread', 'barcode' => '1000002', 'cost_price' => 110, 'selling_price' => 150, 'stock' => 40],
            ['name' => 'Demo Rice 1kg', 'barcode' => '1000003', 'cost_price' => 320, 'selling_price' => 390, 'stock' => 25],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['barcode' => $product['barcode']], [
                ...$product,
                'low_stock_alert' => 5,
                'is_active' => true,
            ]);
        }
    }
}
