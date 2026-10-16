<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
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
            'body' => fake()->paragraph(),
            'parent_id' => null,
            'commentable_type' => PetMemorial::class,
            'commentable_id' => PetMemorial::factory(),
        ];
    }

    /**
     * Attach the comment to any commentable model.
     */
    public function forCommentable(Model $commentable): static
    {
        return $this->state(fn (array $attributes): array => [
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
        ]);
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
