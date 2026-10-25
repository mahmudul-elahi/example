<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PetType;
use App\Enums\PostStatus;
use App\Enums\VisibilityStatus;
use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetMemorial>
 */
class PetMemorialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $born = fake()->dateTimeBetween('-15 years', '-1 year');

        return [
            'user_id' => User::factory(),
            'body' => fake()->paragraphs(3, true),
            'pet_name' => fake()->firstName(),
            'pet_type' => fake()->randomElement(PetType::cases()),
            'pet_born_at' => $born,
            'pet_passed_at' => fake()->dateTimeBetween($born, 'now'),
            'cover_image' => null,
            'status' => PostStatus::Published,
            'visibility' => VisibilityStatus::Public,
            'views_count' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::Draft,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => VisibilityStatus::Private,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
