<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
        ]);

        $adminUser->assignRole('Admin');

        $vendorUser = User::factory()->create([
            'name' => 'Vendor User',
            'email' => 'vendor@demo.com',
            'password' => Hash::make('password'),
        ]);

        $vendorUser->assignRole('Vendor');

        Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Vendor Business ',
            'is_approved' => true,
        ]);

        $vendorUser1 = User::factory()->create([
            'name' => 'Vendor1 User',
            'email' => 'vendor1@demo.com',
            'password' => Hash::make('password'),
        ]);

        $vendorUser1->assignRole('Vendor');

        Vendor::factory()->create([
            'user_id' => $vendorUser1->id,
            'business_name' => 'Vendor1 Business ',
            'is_approved' => true,
        ]);

        $customerUser = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@demo.com',
            'password' => Hash::make('password'),
        ]);

        $customerUser->assignRole('Customer');

        $customer1User = User::factory()->create([
            'name' => 'Customer1 User',
            'email' => 'customer1@demo.com',
            'password' => Hash::make('password'),
        ]);

        $customer1User->assignRole('Customer');
    }
}
