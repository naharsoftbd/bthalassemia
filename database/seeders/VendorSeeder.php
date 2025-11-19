<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        Vendor::factory()
            ->count(8)
            ->approved()
            ->create();

        Vendor::factory()
            ->count(2)
            ->pending()
            ->create();
    }
}
