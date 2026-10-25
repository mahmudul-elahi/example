<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Story;
use App\Models\User;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * Extra expired stories that the active feeds filter out.
     */
    private const int EXPIRED_STORIES = 6;

    /**
     * Seed active stories per author (a mix of images and videos) plus
     * expired stories that the active feeds filter out.
     */
    public function run(): void
    {
        if (Story::query()->exists()) {
            return;
        }

        $authors = $this->authors();

        if ($authors->isEmpty()) {
            return;
        }

        $authors->each(function (User $author, int $index): void {
            Story::factory()
                ->image()
                ->forUser($author)
                ->create();

            if ($index % 2 === 0) {
                Story::factory()
                    ->video()
                    ->forUser($author)
                    ->create();
            } else {
                Story::factory()
                    ->image()
                    ->forUser($author)
                    ->create();
            }
        });

        $authors->take(self::EXPIRED_STORIES)->each(function (User $author): void {
            Story::factory()
                ->expired()
                ->forUser($author)
                ->create();
        });
    }
}
