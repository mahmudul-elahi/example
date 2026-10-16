<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 5, 250);

        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'price' => $price,
            'compare_at_price' => null,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'stock' => fake()->numberBetween(0, 100),
            'in_stock' => true,
            'status' => ProductStatus::Published,
            'cover_image' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Draft,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::OutOfStock,
            'stock' => 0,
            'in_stock' => false,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
