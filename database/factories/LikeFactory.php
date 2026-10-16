<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
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
            'likeable_type' => PetMemorial::class,
            'likeable_id' => PetMemorial::factory(),
        ];
    }

    /**
     * Attach the like to any likeable model.
     */
    public function forLikeable(Model $likeable): static
    {
        return $this->state(fn (array $attributes): array => [
            'likeable_type' => $likeable->getMorphClass(),
            'likeable_id' => $likeable->getKey(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
