<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * How many published posts each author gets.
     */
    private const int POSTS_PER_AUTHOR = 4;

    /**
     * Extra draft posts that the public index filters out.
     */
    private const int DRAFT_POSTS = 8;

    /**
     * Extra featured posts highlighted on the blog.
     */
    private const int FEATURED_POSTS = 5;

    /**
     * How many tags each post carries.
     */
    private const int TAGS_PER_POST = 3;

    /**
     * Seed a batch of published posts per author plus drafts and featured
     * posts, each filed under a category and carrying tags.
     */
    public function run(): void
    {
        if (BlogPost::query()->exists()) {
            return;
        }

        $authors = $this->authors()->values();
        $categories = BlogCategory::query()->get();
        $tags = Tag::query()->get();

        $authors->each(function (User $author, int $index) use ($categories): void {
            BlogPost::factory()
                ->count(self::POSTS_PER_AUTHOR)
                ->forUser($author)
                ->forCategory($categories[$index % $categories->count()])
                ->create(['views_count' => fake()->numberBetween(25, 5000)]);
        });

        BlogPost::factory()
            ->count(self::DRAFT_POSTS)
            ->draft()
            ->forUser($authors->random())
            ->forCategory($categories->random())
            ->create();

        BlogPost::factory()
            ->count(self::FEATURED_POSTS)
            ->featured()
            ->forUser($authors->random())
            ->forCategory($categories->random())
            ->create(['views_count' => fake()->numberBetween(500, 9000)]);

        BlogPost::query()->get()->each(function (BlogPost $post) use ($tags): void {
            $post->tags()->attach($tags->random(self::TAGS_PER_POST));
        });
    }
}
