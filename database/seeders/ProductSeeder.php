<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * Seed two published products per author plus a few drafts and
     * out-of-stock items for the shop.
     */
    public function run(): void
    {
        $authors = $this->authors();

        $authors->each(function (User $author): void {
            Product::factory()
                ->count(2)
                ->forUser($author)
                ->create();
        });

        Product::factory()
            ->count(3)
            ->draft()
            ->forUser($authors->random())
            ->create();

        Product::factory()
            ->count(2)
            ->outOfStock()
            ->forUser($authors->random())
            ->create();
    }
}
