<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed permissions and roles first
        $this->call(PermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ProductSeeder::class);
        

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Login: admin@demo.com / password');
        $this->command->info('Vendor Logins: vendor@demo.com / password');
        $this->command->info('Cumstomer Logins: cumstomer@demo.com / password');
    }
}