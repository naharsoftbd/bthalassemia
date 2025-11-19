<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache first
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            'publish products',
            'unpublish products',

            // Vendor product permissions (for vendors)
            'view own products',
            'create own products',
            'edit own products',
            'delete own products',

            // Product approval permissions (for admins)
            'approve products',
            'reject products',

            // Vendor management permissions
            'view vendors',
            'create vendors',
            'edit vendors',
            'delete vendors',
            'approve vendors',

            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Category permissions
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Order permissions
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'manage orders',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $vendorRole = Role::create(['name' => 'vendor']);
        $vendorPermissions = [
            'view own products',
            'create own products',
            'edit own products',
            'delete own products',
            'view orders',
        ];
        $vendorRole->givePermissionTo($vendorPermissions);

        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view products',
            'view orders',
        ]);

        $this->command->info('Roles and permissions created successfully!');

    }
}
