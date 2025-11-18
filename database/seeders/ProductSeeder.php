<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $product = Product::create([
            'name' => 'Acme T-Shirt',
            'slug' => 'acme-t-shirt',
            'description' => 'Comfortable cotton tee',
            'base_price' => 19.99,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-S-BLACK',
            'name' => 'Small / Black',
            'attributes' => ['size' => 'S', 'color' => 'black'],
            'price' => 19.99,
            'stock' => 20,
            'low_stock_threshold' => 5,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-M-BLUE',
            'name' => 'Medium / Blue',
            'attributes' => ['size' => 'M', 'color' => 'blue'],
            'price' => 19.99,
            'stock' => 3,
            'low_stock_threshold' => 5,
        ]);
    }
}
