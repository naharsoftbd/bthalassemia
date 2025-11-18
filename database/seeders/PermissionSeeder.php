<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache first
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1️⃣ create permissions
        $permissions = ['View', 'Create', 'Edit', 'Delete', 'product.create', 'product.update', 'product.delete'];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[] = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'api']);
        }

        // 2️⃣ create roles
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'api']);
        $vendorRole = Role::create(['name' => 'Vendor', 'guard_name' => 'api']);
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'api']);

        // 3️⃣ assign permissions to roles - FIXED APPROACH
        $adminRole->givePermissionTo($permissions);
        $vendorRole->givePermissionTo($permissions);

        // For customer role, use only the permissions that definitely exist
        $customerRole->givePermissionTo(['View', 'Delete']);

        $adminUser = User::factory()->create([
            'name' => 'Admin',
            'email' => 'demoadmin@demo.com',
            'password' => Hash::make('12345678'),
        ]);
        $adminUser->assignRole($adminRole);

        $vendorUser = User::factory()->create([
            'name' => 'Vendor',
            'email' => 'demovendor@demo.com',
            'password' => Hash::make('12345678'),
        ]);
        $vendorUser->assignRole($vendorRole);

        $customerUser = User::factory()->create([
            'name' => 'Customer',
            'email' => 'democustomer@demo.com',
            'password' => Hash::make('12345678'),
        ]);
        $customerUser->assignRole($customerRole);

    }
}
