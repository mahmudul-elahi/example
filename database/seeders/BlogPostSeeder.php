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
     * Seed two published posts per author plus a few drafts and featured
     * posts, each filed under a category and carrying tags.
     */
    public function run(): void
    {
        if (BlogPost::query()->exists()) {
            return;
        }

        $authors = $this->authors();
        $categories = BlogCategory::query()->get();

        $authors->each(function (User $author) use ($categories): void {
            BlogPost::factory()
                ->count(2)
                ->forUser($author)
                ->forCategory($categories->random())
                ->create();
        });

        BlogPost::factory()
            ->count(3)
            ->draft()
            ->forUser($authors->random())
            ->forCategory($categories->random())
            ->create();

        BlogPost::factory()
            ->count(2)
            ->featured()
            ->forUser($authors->random())
            ->forCategory($categories->random())
            ->create();

        $tags = Tag::query()->get();

        BlogPost::query()->get()->each(function (BlogPost $post) use ($tags): void {
            $post->tags()->attach($tags->random(3));
        });
    }
}
