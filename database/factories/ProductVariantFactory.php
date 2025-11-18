<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $basePrice = $this->faker->randomFloat(2, 5, 500);
        
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper($this->faker->bothify('SKU-####-???')),
            'name' => $this->faker->words(2, true),
            'price' => $basePrice,
            'compare_at_price' => $this->faker->boolean(30) ? $basePrice * 1.2 : null,
            'stock' => $this->faker->numberBetween(0, 1000),
            'low_stock_threshold' => $this->faker->numberBetween(5, 20),
            'attributes' => json_encode([
                'color' => $this->faker->colorName(),
                'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
                'material' => $this->faker->randomElement(['Cotton', 'Polyester', 'Wool', 'Silk']),
            ]),
            'barcode' => $this->faker->isbn13(),
            'weight' => $this->faker->randomFloat(2, 0.1, 10),
            'is_active' => $this->faker->boolean(95),
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

    public function outOfStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => 0,
            ];
        });
    }

    public function lowStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => $this->faker->numberBetween(1, 5),
            ];
        });
    }

    public function withProduct($productId)
    {
        return $this->state(function (array $attributes) use ($productId) {
            return [
                'product_id' => $productId,
            ];
        });
    }
}