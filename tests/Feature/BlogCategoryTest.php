<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('forbids non-admin users from managing categories', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/blog-categories')
        ->assertForbidden();
});

it('requires authentication for category management', function (): void {
    $this->getJson('/api/v1/admin/blog-categories')
        ->assertUnauthorized();
});

it('lets an admin list categories with post counts', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $category = BlogCategory::factory()->create();
    BlogPost::factory()->count(2)->forCategory($category)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/blog-categories')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.items.0.name', $category->name)
        ->assertJsonPath('data.items.0.posts_count', 2);
});

it('lets an admin create a category', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/blog-categories', [
            'name' => 'Pet Loss Support',
            'description' => 'Guides and stories for grieving pet owners.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Pet Loss Support')
        ->assertJsonPath('data.slug', 'pet-loss-support');

    $this->assertDatabaseHas('blog_categories', ['name' => 'Pet Loss Support']);
});

it('rejects a duplicate category name', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    BlogCategory::factory()->create(['name' => 'Memorials']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/blog-categories', ['name' => 'Memorials'])
        ->assertUnprocessable()
        ->assertJsonStructure(['success', 'message', 'errors' => ['name']]);
});

it('lets an admin update a category', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $category = BlogCategory::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/blog-categories/{$category->slug}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('blog_categories', ['id' => $category->id, 'name' => 'New Name']);
});

it('lets an admin delete a category', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $category = BlogCategory::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/blog-categories/{$category->slug}")
        ->assertOk()
        ->assertJsonPath('message', 'Blog category deleted successfully');

    $this->assertDatabaseMissing('blog_categories', ['id' => $category->id]);
});
