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
    private const array CATEGORIES = [
        ['name' => 'Pet Loss', 'description' => 'Coping with the loss of a companion.'],
        ['name' => 'Memorial Stories', 'description' => 'The stories behind the memorials families create.'],
        ['name' => 'Pet Care', 'description' => 'Keeping pets healthy, happy and comfortable.'],
        ['name' => 'Adoption', 'description' => 'Finding a rescue pet a forever home.'],
        ['name' => 'Anniversaries', 'description' => 'Remembering a pet together, year after year.'],
        ['name' => 'Senior Pets', 'description' => 'Caring for companions in their golden years.'],
        ['name' => 'Rescue Stories', 'description' => 'Second chances and the people who make them happen.'],
        ['name' => 'Pet Photography', 'description' => 'Capturing the personality of the pets we love.'],
        ['name' => 'Grief Support', 'description' => 'Guidance for the difficult days after a loss.'],
        ['name' => 'Rainbow Bridge', 'description' => 'Letters and tributes to pets who have passed on.'],
        ['name' => 'Training', 'description' => 'Practical training tips for dogs, cats and small pets.'],
        ['name' => 'Nutrition', 'description' => 'Feeding choices that keep pets thriving.'],
    ];

    /**
     * Seed the categories that blog posts are filed under.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            BlogCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']],
            );
        }
    }
}
