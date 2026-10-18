<?php

declare(strict_types=1);

use App\Enums\ProductStatus;
use App\Enums\Roles;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists only published products to a guest', function (): void {
    Product::factory()->count(2)->create();
    Product::factory()->draft()->create();

    $this->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.total', 2)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [
                    '*' => ['id', 'name', 'slug', 'price', 'stock', 'in_stock', 'status'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ],
        ]);
});

it('shows a published product to a guest', function (): void {
    $product = Product::factory()->create();

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.name', $product->name);
});

it('forbids a guest from viewing a draft product', function (): void {
    $product = Product::factory()->draft()->create();

    $this->getJson("/api/v1/shop/{$product->slug}")
        ->assertForbidden();
});

it('forbids non-admin users from managing products', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/products')
        ->assertForbidden();
});

it('requires authentication for the admin shop area', function (): void {
    $this->getJson('/api/v1/admin/products')
        ->assertUnauthorized();
});

it('lets an admin list all products including drafts', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    Product::factory()->count(2)->create();
    Product::factory()->draft()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/products')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 3);
});

it('lets an admin create a product', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => 'Memorial Photo Frame',
            'price' => '25.00',
            'sku' => 'FRAME-001',
            'stock' => 10,
            'status' => ProductStatus::Published->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Memorial Photo Frame')
        ->assertJsonPath('data.slug', 'memorial-photo-frame')
        ->assertJsonPath('data.price', '25.00')
        ->assertJsonPath('data.in_stock', true)
        ->assertJsonPath('data.status', 'published');

    $this->assertDatabaseHas('products', [
        'name' => 'Memorial Photo Frame',
        'user_id' => $admin->id,
        'sku' => 'FRAME-001',
    ]);
});

it('derives the in_stock flag from the stock count', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => 'Sold Out Urn',
            'price' => '40.00',
            'stock' => 0,
        ])
        ->assertCreated()
        ->assertJsonPath('data.in_stock', false);
});

it('rejects invalid input when an admin creates a product', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => '',
            'price' => '-5',
            'status' => 'nonsense',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['name', 'price', 'status'],
        ]);
});

it('rejects a duplicate sku', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    Product::factory()->create(['sku' => 'TAKEN-1']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/products', [
            'name' => 'Another Product',
            'price' => '10.00',
            'sku' => 'TAKEN-1',
        ])
        ->assertUnprocessable()
        ->assertJsonStructure(['success', 'message', 'errors' => ['sku']]);
});

it('lets an admin update a product', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $product = Product::factory()->create(['stock' => 5]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/products/{$product->slug}", [
            'name' => 'Renamed',
            'stock' => 0,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.in_stock', false);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Renamed']);
});

it('lets an admin delete a product', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('message', 'Product deleted successfully');

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

it('keeps a product cover image on soft delete and removes it on force delete', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();
    $product = Product::factory()->create(['cover_image' => 'covers/frame.jpg']);
    Storage::put('covers/frame.jpg', 'image');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/products/{$product->slug}")
        ->assertOk();

    Storage::assertExists('covers/frame.jpg');

    $product->forceDelete();

    Storage::assertMissing('covers/frame.jpg');
});
