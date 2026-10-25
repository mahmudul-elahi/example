<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\Roles;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists only published posts to a guest', function (): void {
    BlogPost::factory()->count(2)->create();
    BlogPost::factory()->draft()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.total', 2)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [
                    '*' => ['id', 'title', 'slug', 'excerpt', 'status', 'published_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page', 'links' => [['url', 'label', 'active']]],
            ],
        ]);
});

it('shows a published post to a guest and records the view', function (): void {
    $post = BlogPost::factory()->create(['views_count' => 0]);
    $updatedAt = $post->updated_at;

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonPath('data.body', $post->body)
        ->assertJsonPath('data.views_count', 1);

    expect($post->refresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('forbids a guest from viewing a draft post', function (): void {
    $post = BlogPost::factory()->draft()->create();

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertForbidden();
});

it('forbids a guest from viewing a post scheduled for the future', function (): void {
    $post = BlogPost::factory()->create(['published_at' => now()->addDay()]);

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertForbidden();

    $this->travel(2)->days();

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $post->id);
});

it('forbids non-admin users from managing blog posts', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/blog-posts')
        ->assertForbidden();
});

it('requires authentication for the admin blog area', function (): void {
    $this->getJson('/api/v1/admin/blog-posts')
        ->assertUnauthorized();
});

it('lets an admin list all posts including drafts', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    BlogPost::factory()->count(2)->create();
    BlogPost::factory()->draft()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/blog-posts')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 3);
});

it('lets an admin create a post in a category', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $category = BlogCategory::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/blog-posts', [
            'title' => 'Welcome to the Memorial Blog',
            'body' => 'Our very first post.',
            'status' => PostStatus::Published->value,
            'blog_category_id' => $category->id,
            'is_featured' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Welcome to the Memorial Blog')
        ->assertJsonPath('data.slug', 'welcome-to-the-memorial-blog')
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.is_featured', true)
        ->assertJsonPath('data.category.id', $category->id);

    $this->assertDatabaseHas('blog_posts', [
        'title' => 'Welcome to the Memorial Blog',
        'user_id' => $admin->id,
        'blog_category_id' => $category->id,
    ]);
});

it('rejects invalid input when an admin creates a post', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/blog-posts', [
            'title' => '',
            'status' => 'nonsense',
            'blog_category_id' => 999,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['title', 'body', 'status', 'blog_category_id'],
        ]);
});

it('lets an admin update a post', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $post = BlogPost::factory()->draft()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/blog-posts/{$post->slug}", [
            'title' => 'Revised',
            'status' => PostStatus::Published->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Revised')
        ->assertJsonPath('data.status', 'published');

    $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'title' => 'Revised']);
});

it('lets an admin delete a post', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $post = BlogPost::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/blog-posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('message', 'Blog post deleted successfully');

    $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
});

it('keeps a post cover image on soft delete and removes it on force delete', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();
    $post = BlogPost::factory()->create(['cover_image' => 'covers/welcome.jpg']);
    Storage::put('covers/welcome.jpg', 'image');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/blog-posts/{$post->slug}")
        ->assertOk();

    Storage::assertExists('covers/welcome.jpg');

    $post->forceDelete();

    Storage::assertMissing('covers/welcome.jpg');
});

it('does not expose the author email on the public blog', function (): void {
    BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => ['author' => ['id', 'name', 'username', 'avatar']],
                ],
            ],
        ])
        ->assertJsonMissingPath('data.items.0.author.email');
});

it('omits the post body from the public index', function (): void {
    BlogPost::factory()->create();

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonMissingPath('data.items.0.body');
});

it('filters the admin post list by status', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    BlogPost::factory()->count(2)->create();
    BlogPost::factory()->draft()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/blog-posts?status=draft')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);
});

it('stamps the publication date when a draft is first published', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $post = BlogPost::factory()->draft()->create();

    $this->travelTo('2026-01-01 09:00:00');

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/blog-posts/{$post->slug}", ['status' => PostStatus::Published->value])
        ->assertOk();

    expect($post->refresh()->published_at?->toDateTimeString())->toBe('2026-01-01 09:00:00');
});

it('keeps the original publication date when a published post is updated again', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $post = BlogPost::factory()->create(['published_at' => '2025-06-01 08:00:00']);

    $this->travelTo('2026-01-01 09:00:00');

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/blog-posts/{$post->slug}", ['status' => PostStatus::Published->value])
        ->assertOk();

    expect($post->refresh()->published_at?->toDateTimeString())->toBe('2025-06-01 08:00:00');
});
