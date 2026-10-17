<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\Roles;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ],
        ]);
});

it('shows a published post to a guest and records the view', function (): void {
    $post = BlogPost::factory()->create(['views_count' => 0]);

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonPath('data.views_count', 1);
});

it('forbids a guest from viewing a draft post', function (): void {
    $post = BlogPost::factory()->draft()->create();

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertForbidden();
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
