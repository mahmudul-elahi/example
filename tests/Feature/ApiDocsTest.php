<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('viewApiDocs', fn (?User $user = null): bool => true);
});

it('renders the API reference with the Scalar UI', function (): void {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('Scalar.createApiReference', escape: false);
});

it('groups the authentication endpoints under a single Auth tag', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect(array_column($response->json('tags'), 'name'))->toContain('Auth')
        ->and($response->json('paths./v1/auth/me.get.tags'))->toBe(['Auth'])
        ->and($response->json('paths./v1/auth/login.post.tags'))->toBe(['Auth'])
        ->and($response->json('paths./v1/auth/password/reset.post.tags'))->toBe(['Auth']);
});

it('orders the tags by their configured weight', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect(array_column($response->json('tags'), 'name'))
        ->toBe([
            'Auth',
            'Public - Memorials',
            'Public - Blog',
            'Public - Shop',
            'User - Memorials',
            'User - Blog',
            'User - Shop',
            'User - Comments',
            'User - Likes',
            'User - Friends',
            'User - Notifications',
            'Admin - User',
            'Admin - Dashboard',
            'Admin - Blog',
            'Admin - Blog Category',
            'Admin - Shop',
        ]);
});

it('documents the memorial endpoints under the Memorials tags', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/memorials.get.tags'))->toBe(['Public - Memorials'])
        ->and($response->json('paths./v1/memorials.post.tags'))->toBe(['User - Memorials'])
        ->and($response->json('paths./v1/memorials/{memorial}.get.tags'))->toBe(['Public - Memorials'])
        ->and($response->json('paths./v1/memorials/{memorial}.put.tags'))->toBe(['User - Memorials'])
        ->and($response->json('paths./v1/memorials/{memorial}.delete.tags'))->toBe(['User - Memorials']);
});

it('documents the memorial comment and like endpoints', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/memorials/{memorial}/comments.get.tags'))->toBe(['Public - Memorials'])
        ->and($response->json('paths./v1/memorials/{memorial}/comments.post.tags'))->toBe(['User - Memorials'])
        ->and($response->json('paths./v1/comments/{type}/{comment}.delete.tags'))->toBe(['User - Comments'])
        ->and($response->json('paths./v1/memorials/{memorial}/like.post.tags'))->toBe(['User - Memorials']);
});

it('documents the public blog and shop endpoints', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/blog.get.tags'))->toBe(['Public - Blog'])
        ->and($response->json('paths./v1/blog/{blogPost}.get.tags'))->toBe(['Public - Blog'])
        ->and($response->json('paths./v1/shop.get.tags'))->toBe(['Public - Shop'])
        ->and($response->json('paths./v1/shop/{product}.get.tags'))->toBe(['Public - Shop']);
});

it('documents the admin content management endpoints', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/admin/blog-posts.get.tags'))->toBe(['Admin - Blog'])
        ->and($response->json('paths./v1/admin/blog-categories.get.tags'))->toBe(['Admin - Blog Category'])
        ->and($response->json('paths./v1/admin/products.get.tags'))->toBe(['Admin - Shop']);
});

it('documents the blog and shop comment and like endpoints', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/blog/{blogPost}/comments.get.tags'))->toBe(['Public - Blog'])
        ->and($response->json('paths./v1/blog/{blogPost}/comments.post.tags'))->toBe(['User - Blog'])
        ->and($response->json('paths./v1/blog/{blogPost}/like.post.tags'))->toBe(['User - Blog'])
        ->and($response->json('paths./v1/shop/{product}/comments.get.tags'))->toBe(['Public - Shop'])
        ->and($response->json('paths./v1/shop/{product}/comments.post.tags'))->toBe(['User - Shop'])
        ->and($response->json('paths./v1/shop/{product}/like.post.tags'))->toBe(['User - Shop'])
        ->and($response->json('paths./v1/comments/{type}/{comment}.delete.tags'))->toBe(['User - Comments']);
});

it('documents one comment delete endpoint for every content type', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    $parameters = collect($response->json('paths./v1/comments/{type}/{comment}.delete.parameters'));

    expect($parameters->pluck('name')->all())->toBe(['type', 'comment'])
        ->and($parameters->firstWhere('name', 'comment')['schema']['type'])->toBe('integer')
        ->and($parameters->firstWhere('name', 'type')['schema']['type'])->toBe('string')
        // Deletion is addressed by content type now, so the untyped path is gone.
        ->and($response->json('paths./v1/comments/{comment}'))->toBeNull();
});

it('documents one unlike endpoint for every likeable type', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/likes/{type}/{id}.delete.tags'))->toBe(['User - Likes'])
        ->and($response->json('paths./v1/likes/{type}/{id}.delete.parameters.0.name'))->toBe('type')
        ->and($response->json('paths./v1/likes/{type}/{id}.delete.parameters.1.schema.type'))->toBe('integer')
        // Unlike is not resource-scoped any more, so the three old routes stay gone.
        ->and($response->json('paths./v1/memorials/{memorial}/like.delete'))->toBeNull()
        ->and($response->json('paths./v1/blog/{blogPost}/like.delete'))->toBeNull()
        ->and($response->json('paths./v1/shop/{product}/like.delete'))->toBeNull();
});

it('documents the friend request endpoints under the User - Friends tag', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/friend-requests.get.tags'))->toBe(['User - Friends'])
        ->and($response->json('paths./v1/friend-requests.post.tags'))->toBe(['User - Friends'])
        ->and($response->json('paths./v1/friend-requests.post.requestBody.content.application/json.schema.$ref'))
        ->toBe('#/components/schemas/StoreFriendRequestRequest')
        ->and($response->json('paths./v1/friend-requests/count.get.tags'))->toBe(['User - Friends'])
        ->and($response->json('paths./v1/friend-requests/{friendRequest}/accept.post.tags'))->toBe(['User - Friends'])
        ->and($response->json('paths./v1/friend-requests/{friendRequest}/reject.post.tags'))->toBe(['User - Friends'])
        ->and($response->json('paths./v1/friend-requests/{friendRequest}.delete.tags'))->toBe(['User - Friends'])
        ->and($response->json('paths./v1/friends.get.tags'))->toBe(['User - Friends']);
});

it('documents the notification endpoints under the User - Notifications tag', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/notifications.get.tags'))->toBe(['User - Notifications'])
        ->and($response->json('paths./v1/notifications/count.get.tags'))->toBe(['User - Notifications'])
        ->and($response->json('paths./v1/notifications/read-all.post.tags'))->toBe(['User - Notifications'])
        ->and($response->json('paths./v1/notifications/{notification}/read.post.tags'))->toBe(['User - Notifications'])
        ->and($response->json('paths./v1/notifications.get.responses.200.content.application/json.schema.properties.data.properties.items.items.$ref'))
        ->toBe('#/components/schemas/NotificationResource');
});

it('marks the public blog listing as not requiring a bearer token', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/blog.get.security'))->toBe([])
        ->and($response->json('paths./v1/shop.get.security'))->toBe([])
        ->and($response->json('paths./v1/memorials.get.security'))->toBe([])
        ->and($response->json('paths./v1/blog/{blogPost}/comments.get.security'))->toBe([])
        ->and($response->json('paths./v1/shop/{product}/comments.get.security'))->toBe([]);
});

it('documents the admin dashboard endpoint under the Admin - Dashboard tag', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect(array_column($response->json('tags'), 'name'))->toContain('Admin - Dashboard')
        ->and($response->json('paths./v1/admin/dashboard.get.tags'))->toBe(['Admin - Dashboard'])
        ->and($response->json('paths./v1/admin/dashboard.get.security'))->toBeNull();
});

it('documents the admin user management endpoints under the Admin - User tag', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/admin/users.get.tags'))->toBe(['Admin - User'])
        ->and($response->json('paths./v1/admin/users.post.tags'))->toBe(['Admin - User'])
        ->and($response->json('paths./v1/admin/users/{user}.get.tags'))->toBe(['Admin - User'])
        ->and($response->json('paths./v1/admin/users/{user}.put.tags'))->toBe(['Admin - User'])
        ->and($response->json('paths./v1/admin/users/{user}.delete.tags'))->toBe(['Admin - User']);
});

it('documents the admin user list items as UserResource', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/admin/users.get.responses.200.content.application/json.schema.properties.data.properties.items'))
        ->toBe([
            'type' => 'array',
            'items' => ['$ref' => '#/components/schemas/UserResource'],
        ]);
});

it('documents a bearer token security scheme', function (): void {
    $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('components.securitySchemes.sanctum.type', 'http')
        ->assertJsonPath('components.securitySchemes.sanctum.scheme', 'bearer')
        ->assertJsonPath('components.securitySchemes.sanctum.bearerFormat', 'Sanctum')
        ->assertJsonPath('security.0.sanctum', []);
});

it('requires the bearer token for protected endpoints only', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    // Authenticated routes inherit the global bearer requirement.
    expect($response->json('paths./v1/auth/me.get.security'))->toBeNull()
        ->and($response->json('paths./v1/auth/logout.post.security'))->toBeNull();

    // Public routes opt out with an explicit empty security list.
    expect($response->json('paths./v1/auth/login.post.security'))->toBe([])
        ->and($response->json('paths./v1/auth/register.post.security'))->toBe([]);
});

it('documents the token returned by login and register', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/auth/login.post.responses.200.content.application/json.schema.properties.data.properties.token.type'))
        ->toBe('string');
});
