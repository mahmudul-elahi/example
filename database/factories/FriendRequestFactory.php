<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FriendRequest>
 */
class FriendRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'status' => FriendRequestStatus::Pending,
            'responded_at' => null,
        ];
    }

    public function from(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'sender_id' => $user->id,
        ]);
    }

    public function to(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'receiver_id' => $user->id,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FriendRequestStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FriendRequestStatus::Rejected,
            'responded_at' => now(),
        ]);
    }
}
