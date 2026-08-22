<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedUser>
 */
class BlockedUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blocker_id' => User::factory(),
            'blocked_id' => User::factory(),
        ];
    }

    public function blocker(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'blocker_id' => $user->id,
        ]);
    }

    public function blocked(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'blocked_id' => $user->id,
        ]);
    }
}
