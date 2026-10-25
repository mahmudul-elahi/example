<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StoryMediaType;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'media_path' => 'stories/'.fake()->uuid().'.jpg',
            'media_type' => StoryMediaType::Image,
            'caption' => fake()->optional()->sentence(),
            'expires_at' => now()->addDay(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes): array => [
            'media_type' => StoryMediaType::Image,
            'media_path' => 'stories/'.fake()->uuid().'.jpg',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'media_type' => StoryMediaType::Video,
            'media_path' => 'stories/'.fake()->uuid().'.mp4',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }
}
