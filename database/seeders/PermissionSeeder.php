<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'View',
            'Create',
            'Edit',
            'Delete',
        ];

        foreach ($permissions as $key => $value) {
            Permission::create(['name' => $value]);
        }

        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->givePermissionTo('View');
        $adminRole->givePermissionTo('Create');
        $adminRole->givePermissionTo('Edit');
        $adminRole->givePermissionTo('Delete');

        $adminUser = User::factory()->create([
            'name' => 'Admin',
            'email' => 'demoadmin@demo.com',
            'password' => Hash::make('12345678'),
        ]);
        $adminUser->assignRole($adminRole);

        $vendorRole = Role::create(['name' => 'Vendor']);
        $vendorRole->givePermissionTo('View');
        $vendorRole->givePermissionTo('Create');
        $vendorRole->givePermissionTo('Edit');
        $vendorRole->givePermissionTo('Delete');

        $vendorUser = User::factory()->create([
            'name' => 'Vendor',
            'email' => 'demovendor@demo.com',
            'password' => Hash::make('12345678'),
        ]);
        $vendorUser->assignRole($vendorRole);

        $customerRole = Role::create(['name' => 'Customer']);
        $customerRole->givePermissionTo('View');
        $customerRole->givePermissionTo('Create');
        $customerRole->givePermissionTo('Edit');
        $customerRole->givePermissionTo('Delete');

        $customerUser = User::factory()->create([
            'name' => 'Customer',
            'email' => 'democustomer@demo.com',
            'password' => Hash::make('12345678'),
        ]);
        $customerUser->assignRole($customerRole);
    }
}
