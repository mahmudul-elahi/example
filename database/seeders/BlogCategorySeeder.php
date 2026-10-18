<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * The categories the blog is seeded with.
     */
    private const CATEGORIES = [
        ['name' => 'Pet Loss', 'description' => 'Coping with the loss of a companion.'],
        ['name' => 'Memorial Stories', 'description' => 'The stories behind the memorials families create.'],
        ['name' => 'Pet Care', 'description' => 'Keeping pets healthy, happy and comfortable.'],
        ['name' => 'Adoption', 'description' => 'Finding a rescue pet a forever home.'],
        ['name' => 'Anniversaries', 'description' => 'Remembering a pet together, year after year.'],
    ];

    /**
     * Seed the categories that blog posts are filed under.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            BlogCategory::factory()->create($category);
        }
    }
}
