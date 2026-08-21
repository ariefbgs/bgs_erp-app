<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['product_code' => 'PRD001', 'name' => 'Laptop Asus', 'unit' => 'pcs', 'price' => 7500000, 'stock' => 10],
            ['product_code' => 'PRD002', 'name' => 'Mouse Logitech', 'unit' => 'pcs', 'price' => 250000, 'stock' => 50],
            ['product_code' => 'PRD003', 'name' => 'Keyboard Mechanical', 'unit' => 'pcs', 'price' => 500000, 'stock' => 30],
            ['product_code' => 'PRD004', 'name' => 'Monitor LG 24"', 'unit' => 'pcs', 'price' => 2000000, 'stock' => 15],
            ['product_code' => 'PRD005', 'name' => 'Printer Epson', 'unit' => 'pcs', 'price' => 1800000, 'stock' => 8],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}