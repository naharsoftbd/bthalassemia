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

       $vendorUser =  User::factory()->create([
            'name' => 'Vendor User',
            'email' => 'vendor@demo.com',
            'password' => Hash::make('password'),
        ]);

        $vendorUser->assignRole('Vendor');

        Vendor::factory()->create([
                'user_id' => $vendorUser->id,
                'business_name' => "Vendor Business ",
                'is_approved' => true
            ]);

        $customerUser =  User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@demo.com',
            'password' => Hash::make('password'),
        ]);

        $customerUser->assignRole('Customer');
    }
}