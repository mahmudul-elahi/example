<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * How many listed models of each commentable type get a seeded thread.
     */
    private const int COMMENTABLE_COUNT = 5;

    /**
     * How many top-level comments each thread starts with.
     */
    private const int COMMENTS_PER_MODEL = 2;

    /**
     * Seed comment threads on blog posts, memorials and products — the models
     * that expose a comments count in their API responses — including one
     * reply per comment.
     */
    public function run(): void
    {
        if (Comment::query()->exists()) {
            return;
        }

        $authors = $this->authors();

        foreach ($this->commentables() as $commentable) {
            $comments = Comment::factory()
                ->count(self::COMMENTS_PER_MODEL)
                ->forCommentable($commentable)
                ->forUser($authors->random())
                ->create();

            $comments->each(function (Comment $comment) use ($authors, $commentable): void {
                Comment::factory()
                    ->replyTo($comment)
                    ->forCommentable($commentable)
                    ->forUser($authors->random())
                    ->create();
            });
        }
    }

    /**
     * The first few publicly listed models of every commentable type.
     *
     * The lists are flattened into one array instead of merged: merging
     * Eloquent collections keys them by primary key, so models of different
     * types that share an id would overwrite each other.
     *
     * @return list<Model>
     */
    private function commentables(): array
    {
        return [
            ...BlogPost::query()->published()->limit(self::COMMENTABLE_COUNT)->get()->all(),
            ...PetMemorial::query()->publiclyVisible()->limit(self::COMMENTABLE_COUNT)->get()->all(),
            ...Product::query()->published()->limit(self::COMMENTABLE_COUNT)->get()->all(),
        ];
    }
}
