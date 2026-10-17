<?php

declare(strict_types=1);

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the memorials index from the cache until the lifetime expires', function (): void {
    PetMemorial::factory()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    PetMemorial::factory()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    $this->travel(11)->minutes();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('serves the shop index from the cache', function (): void {
    Product::factory()->create();

    $this->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    Product::factory()->create();

    $this->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);
});

it('serves a product from the cache', function (): void {
    $product = Product::factory()->create(['name' => 'Original Name']);

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Original Name');

    $product->update(['name' => 'Renamed']);

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Original Name');
});

it('serves the blog index from the cache', function (): void {
    BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);
});

it('serves a memorial\'s comments from the cache', function (): void {
    $memorial = PetMemorial::factory()->create();
    Comment::factory()->forCommentable($memorial)->create();

    $this->getJson("/api/v1/memorials/{$memorial->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    Comment::factory()->forCommentable($memorial)->create();

    $this->getJson("/api/v1/memorials/{$memorial->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);
});

it('does not cache the memorial show route so every visit records a view', function (): void {
    $memorial = PetMemorial::factory()->create(['views_count' => 0]);

    $this->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('data.views_count', 1);

    $this->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('data.views_count', 2);

    expect($memorial->refresh()->views_count)->toBe(2);
});

it('does not cache the blog show route so every visit records a view', function (): void {
    $post = BlogPost::factory()->create(['views_count' => 0]);

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.views_count', 1);

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.views_count', 2);

    expect($post->refresh()->views_count)->toBe(2);
});
