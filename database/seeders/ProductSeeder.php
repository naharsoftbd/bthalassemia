<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Vendor;
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
    }
}