<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $userOneId = min($user1->id, $user2->id);
        $userTwoId = max($user1->id, $user2->id);

        return [
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
            'last_message_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Conversation $conversation): void {
            ConversationParticipant::query()->firstOrCreate([
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->user_one_id,
            ]);
            ConversationParticipant::query()->firstOrCreate([
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->user_two_id,
            ]);
        });
    }

    public function between(User $userA, User $userB): static
    {
        $userOneId = min($userA->id, $userB->id);
        $userTwoId = max($userA->id, $userB->id);

        return $this->state(fn (array $attributes): array => [
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }
}
