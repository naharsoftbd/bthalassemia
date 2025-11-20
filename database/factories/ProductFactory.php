<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'vendor_id' => Vendor::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->sentence(),
            'base_price' => $this->faker->randomFloat(2, 10, 1000),
            'is_active' => true,
            'status' => 'published',
            'is_approved' => true,
            'tags' => json_encode($this->faker->words(5)),
            'meta_title' => $this->faker->sentence(),
            'meta_description' => $this->faker->paragraph(),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
                'is_active' => true,
            ];
        });
    }

    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
            ];
        });
    }

    public function pendingReview()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending_review',
            ];
        });
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => true,
            ];
        });
    }

    public function withoutVendor()
    {
        return $this->state(function (array $attributes) {
            return [
                'vendor_id' => null,
            ];
        });
    }

    public function withVendor($vendorId)
    {
        return $this->state(function (array $attributes) use ($vendorId) {
            return [
                'vendor_id' => $vendorId,
            ];
        });
    }
}
