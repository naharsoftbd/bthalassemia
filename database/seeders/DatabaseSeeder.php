<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed permissions and roles first
        $this->call(PermissionSeeder::class);

        // Create Admin User
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $adminUser->assignRole('admin');

        // Create Vendor Users
        $vendorUsers = User::factory()
            ->count(5)
            ->create();

        // Assign vendor role and create vendor profiles
        foreach ($vendorUsers as $index => $user) {
            $user->assignRole('vendor');
            
            Vendor::factory()->create([
                'user_id' => $user->id,
                'business_name' => "Vendor Business " . ($index + 1),
                'is_approved' => $index < 4, // First 4 approved, last one pending
            ]);
        }

        // Create Customer Users
        $customerUsers = User::factory()
            ->count(10)
            ->create();
            
        foreach ($customerUsers as $user) {
            $user->assignRole('customer');
        }

        // Create products without vendors (admin products)
        Product::factory()
            ->count(10)
            ->withoutVendor()
            ->published()
            ->approved()
            ->create();

        // Create products for each vendor
        $vendors = Vendor::where('is_approved', true)->get();
        
        foreach ($vendors as $vendor) {
            Product::factory()
                ->count(8)
                ->withVendor($vendor->id)
                ->published()
                ->approved()
                ->create();

            // Create some draft products for vendors
            Product::factory()
                ->count(3)
                ->withVendor($vendor->id)
                ->draft()
                ->create();

            // Create some pending review products
            Product::factory()
                ->count(2)
                ->withVendor($vendor->id)
                ->pendingReview()
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

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Login: admin@example.com / password');
        $this->command->info('Vendor Logins: Check users table for vendor emails');
        $this->command->info('All vendor passwords: password');
    }
}