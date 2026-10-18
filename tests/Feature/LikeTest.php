<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlogPost;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 when no token is provided', function (): void {
    $memorial = PetMemorial::factory()->create();

    $this->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertUnauthorized();

    $this->deleteJson("/api/v1/likes/memorials/{$memorial->id}")
        ->assertUnauthorized();
});

it('lets a user like a memorial', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.likes_count', 1);

    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'likeable_type' => PetMemorial::class,
        'likeable_id' => $memorial->id,
    ]);
});

it('does not duplicate the like when a user likes twice', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 1);

    expect($memorial->likes()->count())->toBe(1);
});

it('lets a user unlike a memorial', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();
    $memorial->likes()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/likes/memorials/{$memorial->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.likes_count', 0);

    expect($memorial->likes()->count())->toBe(0);
});

it('only removes the like of the authenticated user', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();
    $memorial->likes()->create(['user_id' => $user->id]);
    $memorial->likes()->create(['user_id' => $other->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/likes/memorials/{$memorial->id}")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 1);

    expect($memorial->likes()->count())->toBe(1)
        ->and($memorial->likes()->where('user_id', $other->id)->exists())->toBeTrue();
});

it('forbids a user from liking a private memorial they cannot view', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->private()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertForbidden();

    expect($memorial->likes()->count())->toBe(0);
});

it('forbids a user from unliking content they cannot view', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $post = BlogPost::factory()->draft()->create();
    $post->likes()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/likes/blog-posts/{$post->id}")
        ->assertForbidden();

    expect($post->likes()->count())->toBe(1);
});

it('returns 404 when the type is not likeable', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/likes/tags/1')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('returns 404 when the liked model does not exist', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/likes/blog-posts/999999')
        ->assertNotFound();
});

it('lets a user like and unlike a blog post', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $post = BlogPost::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/blog/{$post->slug}/like")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.likes_count', 1);

    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'likeable_type' => BlogPost::class,
        'likeable_id' => $post->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/likes/blog-posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 0);

    expect($post->likes()->count())->toBe(0);
});

it('lets a user like and unlike a product', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $product = Product::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/shop/{$product->slug}/like")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.likes_count', 1);

    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'likeable_type' => Product::class,
        'likeable_id' => $product->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/likes/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 0);

    expect($product->likes()->count())->toBe(0);
});

it('forbids a user from liking a draft blog post they cannot view', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $post = BlogPost::factory()->draft()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/blog/{$post->slug}/like")
        ->assertForbidden();

    expect($post->likes()->count())->toBe(0);
});

it('forbids a user from liking a draft product they cannot view', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $product = Product::factory()->draft()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/shop/{$product->slug}/like")
        ->assertForbidden();

    expect($product->likes()->count())->toBe(0);
});
