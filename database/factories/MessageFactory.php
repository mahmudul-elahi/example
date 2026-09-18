<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'type' => MessageType::Text,
            'body' => fake()->sentence(),
            'attachment_path' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'audio_duration' => null,
            'deleted_for_everyone_at' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::Image,
            'body' => null,
            'attachment_path' => 'messages/test-image.jpg',
            'attachment_mime' => 'image/jpeg',
            'attachment_size' => 10240,
        ]);
    }

    public function audio(?int $duration = 15): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::Audio,
            'body' => null,
            'attachment_path' => 'messages/test-audio.mp3',
            'attachment_mime' => 'audio/mpeg',
            'attachment_size' => 20480,
            'audio_duration' => $duration,
        ]);
    }

    public function file(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::File,
            'body' => null,
            'attachment_path' => 'messages/test-file.pdf',
            'attachment_mime' => 'application/pdf',
            'attachment_size' => 51200,
        ]);
    }

    public function deletedForEveryone(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_for_everyone_at' => now(),
        ]);
    }
}
