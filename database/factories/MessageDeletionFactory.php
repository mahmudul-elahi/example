<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageDeletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageDeletion>
 */
class MessageDeletionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'user_id' => User::factory(),
        ];
    }
}
