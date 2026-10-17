<?php

declare(strict_types=1);

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the memorials index from the cache and invalidates it when a memorial is created', function (): void {
    PetMemorial::factory()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves from cache on subsequent read
    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Creating a memorial invalidates the cache
    PetMemorial::factory()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('serves the shop index from the cache and invalidates it when a product is created', function (): void {
    Product::factory()->create();

    $this->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves from cache on subsequent read
    $this->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Creating a product invalidates the cache
    Product::factory()->create();

    $this->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('serves a product from the cache and invalidates it when updated', function (): void {
    $product = Product::factory()->create(['name' => 'Original Name']);

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Original Name');

    // Serves from cache on subsequent read
    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Original Name');

    // Updating the product invalidates the show cache
    $product->update(['name' => 'Renamed']);

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');
});

it('serves the blog index from the cache and invalidates it when a post is created', function (): void {
    BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves from cache on subsequent read
    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Creating a post invalidates the cache
    BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('invalidates the blog index cache when a post is updated', function (): void {
    $post = BlogPost::factory()->create(['title' => 'Original Title']);

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.title', 'Original Title');

    $post->update(['title' => 'Updated Title']);

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.title', 'Updated Title');
});

it('invalidates the blog index cache when a post is deleted', function (): void {
    $post = BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    $post->delete();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 0);
});

it('serves a memorial\'s comments from the cache and invalidates it when a comment is added', function (): void {
    $memorial = PetMemorial::factory()->create();
    Comment::factory()->forCommentable($memorial)->create();

    $this->getJson("/api/v1/memorials/{$memorial->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves from cache on subsequent read
    $this->getJson("/api/v1/memorials/{$memorial->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Adding a comment invalidates the comments thread
    Comment::factory()->forCommentable($memorial)->create();

    $this->getJson("/api/v1/memorials/{$memorial->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('invalidates the memorial index comments count when a comment is added', function (): void {
    $memorial = PetMemorial::factory()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.items.0.comments_count', 0);

    Comment::factory()->forCommentable($memorial)->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.items.0.comments_count', 1);
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
