<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
        $this->command->info('Vendor Logins: vendor1@demo.com / password');
        $this->command->info('Cumstomer Logins: cumstomer@demo.com / password');
        $this->command->info('Cumstomer Logins: cumstomer1@demo.com / password');
    }
}
