<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class LikeSeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * How many listed models of each likeable type get seeded likes.
     */
    private const int LIKEABLE_COUNT = 5;

    /**
     * How many distinct users like each of those models.
     */
    private const int LIKES_PER_MODEL = 3;

    /**
     * Seed likes on blog posts, memorials and products — the models that
     * expose a likes count in their API responses.
     */
    public function run(): void
    {
        if (Like::query()->exists()) {
            return;
        }

        $authors = $this->authors();

        foreach ($this->likeables() as $likeable) {
            $authors->random(self::LIKES_PER_MODEL)->each(function (User $author) use ($likeable): void {
                Like::factory()
                    ->forLikeable($likeable)
                    ->forUser($author)
                    ->create();
            });
        }
    }

    /**
     * The first few publicly listed models of every likeable type.
     *
     * The lists are flattened into one array instead of merged: merging
     * Eloquent collections keys them by primary key, so models of different
     * types that share an id would overwrite each other.
     *
     * @return list<Model>
     */
    private function likeables(): array
    {
        return [
            ...BlogPost::query()->published()->limit(self::LIKEABLE_COUNT)->get()->all(),
            ...PetMemorial::query()->publiclyVisible()->limit(self::LIKEABLE_COUNT)->get()->all(),
            ...Product::query()->published()->limit(self::LIKEABLE_COUNT)->get()->all(),
        ];
    }
}
