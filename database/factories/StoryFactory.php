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
}
