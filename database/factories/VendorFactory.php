<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->afterCreating(function (User $user) {
                $user->assignRole('Vendor'); // Assign "Vendor" role automatically
            }),
            'business_name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'is_approved' => $this->faker->boolean(80), // 80% approved
        ];
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => true,
            ];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => false,
            ];
        });
    }
}