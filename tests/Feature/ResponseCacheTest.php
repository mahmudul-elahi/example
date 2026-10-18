<?php

declare(strict_types=1);

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use App\Repositories\LikeRepositoryInterface;
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
        ->assertHeader('X-Cache-Status', 'HIT')
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

it('serves a blog post\'s comments from the cache and invalidates it when a comment is added', function (): void {
    $post = BlogPost::factory()->create();
    Comment::factory()->forCommentable($post)->create();

    $this->getJson("/api/v1/blog/{$post->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves from cache on subsequent read
    $this->getJson("/api/v1/blog/{$post->slug}/comments")
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'HIT')
        ->assertJsonPath('data.meta.total', 1);

    // Adding a comment on the post invalidates the thread
    Comment::factory()->forCommentable($post)->create();

    $this->getJson("/api/v1/blog/{$post->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('serves a product\'s comments from the cache and invalidates it when a comment is added', function (): void {
    $product = Product::factory()->create();
    Comment::factory()->forCommentable($product)->create();

    $this->getJson("/api/v1/shop/{$product->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves from cache on subsequent read
    $this->getJson("/api/v1/shop/{$product->slug}/comments")
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'HIT')
        ->assertJsonPath('data.meta.total', 1);

    // Adding a comment on the product invalidates the thread
    Comment::factory()->forCommentable($product)->create();

    $this->getJson("/api/v1/shop/{$product->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('invalidates the cached product when a comment on it changes', function (): void {
    $product = Product::factory()->create();

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.comments_count', 0);

    Comment::factory()->forCommentable($product)->create();

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.comments_count', 1);
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

it('invalidates cached pagination and search variants of a cached index', function (): void {
    PetMemorial::factory()->create(['title' => 'Findable Memorial']);

    $this->getJson('/api/v1/memorials?search=Findable')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    // Serves the search variant from cache
    $this->getJson('/api/v1/memorials?search=Findable')
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'HIT')
        ->assertJsonPath('data.meta.total', 1);

    // Creating a matching memorial invalidates the search variant too
    PetMemorial::factory()->create(['title' => 'Another Findable Memorial']);

    $this->getJson('/api/v1/memorials?search=Findable')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);

    // The bare index URL is invalidated in the same sweep
    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 2);
});

it('invalidates the memorials index cache when a memorial is liked and unliked', function (): void {
    $memorial = PetMemorial::factory()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.items.0.likes_count', 0);

    $like = Like::factory()->forLikeable($memorial)->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.items.0.likes_count', 1);

    // Bulk deletes through the repository must also invalidate
    resolve(LikeRepositoryInterface::class)->delete($like->user, $memorial);

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.items.0.likes_count', 0);
});

it('invalidates the blog index cache when a category is renamed', function (): void {
    $category = BlogCategory::factory()->create(['name' => 'Original Name']);
    BlogPost::factory()->forCategory($category)->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.category.name', 'Original Name');

    $category->update(['name' => 'Renamed Category']);

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.category.name', 'Renamed Category');
});

it('isolates cached public responses per authenticated user while guests share one entry', function (): void {
    PetMemorial::factory()->create();

    // Guests share a single entry between them.
    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS');

    $guest = $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'HIT');

    // The default cache profile keys entries per authenticated user: each
    // account gets its own entry and never receives another user's response.
    $firstRead = $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS');

    $secondUser = $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS');

    // Public endpoints expose no per-user data, so every entry is identical.
    expect($firstRead->json())->toBe($guest->json())
        ->and($secondUser->json())->toBe($guest->json());
});

it('invalidates the blog index cache when a comment on a post changes', function (): void {
    $post = BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.comments_count', 0);

    Comment::factory()->forCommentable($post)->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.comments_count', 1);

    $post->comments()->first()->delete();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('data.items.0.comments_count', 0);
});

it('keeps login out of the response cache so tokens are never replayed between accounts', function (): void {
    User::factory()->create(['email' => 'jane@example.com', 'password' => 'password123']);
    User::factory()->create(['email' => 'john@example.com', 'password' => 'password123']);

    $janeLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])->assertOk();

    $johnLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
    ])->assertOk();

    // The login route sits outside the CacheResponse middleware: no cache
    // headers are attached and Jane's token is never served to John.
    expect($janeLogin->headers->has('X-Cache-Status'))->toBeFalse()
        ->and($johnLogin->json('data.token'))->not->toBe($janeLogin->json('data.token'))
        ->and($johnLogin->json('data.user.email'))->toBe('john@example.com');
});
