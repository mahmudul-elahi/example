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
     * How many published products each author gets.
     */
    private const int PRODUCTS_PER_AUTHOR = 4;

    /**
     * Extra draft products that the public shop filters out.
     */
    private const int DRAFT_PRODUCTS = 10;

    /**
     * Extra out-of-stock products so the shop shows availability states.
     */
    private const int OUT_OF_STOCK_PRODUCTS = 8;

    /**
     * Seed a batch of published products per author plus drafts and
     * out-of-stock items for the shop.
     */
    public function run(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        $authors = $this->authors();

        $authors->each(function (User $author): void {
            Product::factory()
                ->count(self::PRODUCTS_PER_AUTHOR)
                ->forUser($author)
                ->create();
        });

        Product::factory()
            ->count(self::DRAFT_PRODUCTS)
            ->draft()
            ->forUser($authors->random())
            ->create();

        Product::factory()
            ->count(self::OUT_OF_STOCK_PRODUCTS)
            ->outOfStock()
            ->forUser($authors->random())
            ->create();
    }
}
