<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * The tags blog posts are labelled with.
     */
    private const array TAGS = [
        'dogs',
        'cats',
        'birds',
        'rabbits',
        'adoption',
        'grief',
        'memorials',
        'health',
        'training',
        'stories',
    ];

    /**
     * Seed the tags used to label blog posts.
     */
    public function run(): void
    {
        foreach (self::TAGS as $tag) {
            Tag::query()->firstOrCreate(['name' => $tag]);
        }
    }
}
