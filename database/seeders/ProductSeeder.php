<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Admin products
        Product::factory()
            ->count(15)
            ->withoutVendor()
            ->published()
            ->create();

        // Vendor products
        $vendors = Vendor::all();
        
        foreach ($vendors as $vendor) {
            Product::factory()
                ->count(rand(5, 12))
                ->withVendor($vendor->id)
                ->published()
                ->create();
        }

        // Create variants for products
        $products = Product::all();
        
        foreach ($products as $product) {
            // Create 1-4 variants for each product
            $variantCount = rand(1, 4);
            
            ProductVariant::factory()
                ->count($variantCount)
                ->withProduct($product->id)
                ->active()
                ->create();

            // Create some inactive variants
            if (rand(0, 1)) {
                ProductVariant::factory()
                    ->withProduct($product->id)
                    ->inactive()
                    ->create();
            }

            // Create some out of stock variants
            if (rand(0, 1)) {
                ProductVariant::factory()
                    ->withProduct($product->id)
                    ->outOfStock()
                    ->create();
            }

            // Create some low stock variants
            if (rand(0, 1)) {
                ProductVariant::factory()
                    ->withProduct($product->id)
                    ->lowStock()
                    ->create();
            }
        }
    }
}