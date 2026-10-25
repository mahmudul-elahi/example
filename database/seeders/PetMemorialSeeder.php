<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PetMemorial;
use App\Models\User;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Seeder;

class PetMemorialSeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * How many published memorials each author gets.
     */
    private const int MEMORIALS_PER_AUTHOR = 3;

    /**
     * Extra drafts that the public memorial index filters out.
     */
    private const int DRAFT_MEMORIALS = 8;

    /**
     * Extra private memorials owned by a single user.
     */
    private const int PRIVATE_MEMORIALS = 6;

    /**
     * Seed published memorials per author plus drafts and private memorials,
     * which the public index filters out.
     */
    public function run(): void
    {
        if (PetMemorial::query()->exists()) {
            return;
        }

        $authors = $this->authors();

        $authors->each(function (User $author): void {
            PetMemorial::factory()
                ->count(self::MEMORIALS_PER_AUTHOR)
                ->forUser($author)
                ->create(['views_count' => fake()->numberBetween(10, 3000)]);
        });

        PetMemorial::factory()
            ->count(self::DRAFT_MEMORIALS)
            ->draft()
            ->forUser($authors->random())
            ->create();

        PetMemorial::factory()
            ->count(self::PRIVATE_MEMORIALS)
            ->private()
            ->forUser($authors->random())
            ->create(['views_count' => fake()->numberBetween(0, 50)]);
    }
}
