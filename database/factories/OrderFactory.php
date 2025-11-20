<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(), // always provided in tests
            'order_number' => 'ORD-'.$this->faker->unique()->numerify('######'),

            'status' => $this->faker->randomElement([
                'Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled',
            ]),

            'subtotal' => $this->faker->randomFloat(2, 50, 500),
            'tax_amount' => $this->faker->randomFloat(2, 5, 50),
            'shipping_cost' => $this->faker->randomFloat(2, 5, 30),
            'discount_amount' => $this->faker->randomFloat(2, 0, 20),
            'total' => $this->faker->randomFloat(2, 60, 6000),

            'customer_email' => User::factory(), // provided in tests
            'customer_phone' => $this->faker->phoneNumber(),

            'shipping_address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->stateAbbr(),
                'zip_code' => $this->faker->postcode(),
                'country' => $this->faker->country(),
            ],

            'billing_address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->stateAbbr(),
                'zip_code' => $this->faker->postcode(),
                'country' => $this->faker->country(),
            ],

            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'payment_method' => $this->faker->randomElement(['cod', 'card', 'bkash', 'nagad']),
            'transaction_id' => $this->faker->uuid(),

            'shipping_method' => $this->faker->randomElement(['standard', 'express']),
            'tracking_number' => $this->faker->optional()->numerify('TRK########'),
        ];
    }
}
