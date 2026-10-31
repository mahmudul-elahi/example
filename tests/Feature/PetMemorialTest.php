<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\Roles;
use App\Models\PetMemorial;
use App\Models\PetMemorialImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('requires authentication to list memorials', function (): void {
    $this->getJson('/api/v1/memorials')
        ->assertUnauthorized();
});

it('requires authentication to list own memorials', function (): void {
    $this->getJson('/api/v1/memorials/mine')
        ->assertUnauthorized();
});

it('lists all memorials owned by the authenticated user including drafts and private ones', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();

    $public = PetMemorial::factory()->forUser($owner)->create();
    $draft = PetMemorial::factory()->draft()->forUser($owner)->create();
    $private = PetMemorial::factory()->private()->forUser($owner)->create();
    $otherMemorial = PetMemorial::factory()->forUser($other)->create();

    $response = $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/memorials/mine')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'My memorials')
        ->assertJsonCount(3, 'data.items');

    $ids = collect($response->json('data.items'))->pluck('id')->all();
    expect($ids)->toContain($public->id, $draft->id, $private->id)
        ->and($ids)->not->toContain($otherMemorial->id);
});

it('filters own memorials by search', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    PetMemorial::factory()->forUser($owner)->create(['pet_name' => 'Rex']);
    PetMemorial::factory()->forUser($owner)->create(['pet_name' => 'Bella']);

    $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/memorials/mine?search=Rex')
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.pet_name', 'Rex');
});

it('lists only publicly visible memorials to an authenticated user', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    PetMemorial::factory()->count(2)->create();
    PetMemorial::factory()->draft()->create();
    PetMemorial::factory()->private()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.per_page', 15)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'slug',
                        'body',
                        'pet_name',
                        'status',
                        'visibility',
                        'author' => ['id', 'name'],
                    ],
                ],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor', 'has_next'],
            ],
        ]);
});

it('includes the memorial body in the index', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create(['body' => 'A gentle soul who loved the beach.']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('data.items.0.body', 'A gentle soul who loved the beach.')
        ->assertJsonMissingPath('data.items.0.title')
        ->assertJsonMissingPath('data.items.0.description')
        ->assertJsonPath('data.items.0.id', $memorial->id);
});

it('filters memorials by search', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    PetMemorial::factory()->create(['pet_name' => 'Rex']);
    PetMemorial::factory()->create(['pet_name' => 'Bella']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/memorials?search=Rex')
        ->assertOk()
        ->assertJsonPath('data.meta.per_page', 15)
        ->assertJsonPath('data.items.0.pet_name', 'Rex');
});

it('shows a public memorial to a guest and records the view', function (): void {
    $memorial = PetMemorial::factory()->create(['views_count' => 0]);
    $updatedAt = $memorial->updated_at;

    $this->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $memorial->id)
        ->assertJsonPath('data.body', $memorial->body)
        ->assertJsonPath('data.views_count', 1);

    expect($memorial->refresh()->views_count)->toBe(1)
        ->and($memorial->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('forbids a guest from viewing a private memorial', function (): void {
    $memorial = PetMemorial::factory()->private()->create();

    $this->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertForbidden();
});

it('lets the owner view their own private memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->private()->forUser($owner)->create();

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $memorial->id);
});

it('requires authentication to create a memorial', function (): void {
    $this->postJson('/api/v1/memorials', [])
        ->assertUnauthorized();
});

it('creates a memorial for the authenticated user', function (): void {
    Storage::fake();

    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->post('/api/v1/memorials', [
            'body' => 'A loving companion.',
            'pet_name' => 'Bella',
            'pet_type' => 'dog',
            'images' => [
                UploadedFile::fake()->image('bella1.jpg'),
                UploadedFile::fake()->image('bella2.jpg'),
            ],
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.body', 'A loving companion.')
        ->assertJsonPath('data.slug', 'bella')
        ->assertJsonPath('data.pet_type', 'dog')
        ->assertJsonPath('data.status', PostStatus::Published->value)
        ->assertJsonPath('data.visibility', 'public')
        ->assertJsonCount(2, 'data.images');

    $this->assertDatabaseHas('pet_memorials', [
        'pet_name' => 'Bella',
        'user_id' => $user->id,
    ]);

    $memorial = PetMemorial::query()->where('pet_name', 'Bella')->firstOrFail();
    expect($memorial->images)->toHaveCount(2);

    foreach ($memorial->images as $image) {
        Storage::assertExists($image->path);
    }
});

it('rejects invalid input when creating a memorial', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'body' => '',
            'pet_type' => 'dragon',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['body', 'pet_name', 'pet_type'],
        ]);
});

it('lets the owner update their memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", [
            'body' => 'Updated body.',
            'visibility' => 'private',
        ])
        ->assertOk()
        ->assertJsonPath('data.body', 'Updated body.')
        ->assertJsonPath('data.visibility', 'private');

    $this->assertDatabaseHas('pet_memorials', [
        'id' => $memorial->id,
        'body' => 'Updated body.',
    ]);
});

it('forbids another user from updating a memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", ['body' => 'Hijacked'])
        ->assertForbidden();
});

it('lets an admin update any memorial', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $memorial = PetMemorial::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", ['body' => 'Moderated'])
        ->assertOk()
        ->assertJsonPath('data.body', 'Moderated');
});

it('lets the owner delete their memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('message', 'Memorial deleted successfully');

    $this->assertSoftDeleted('pet_memorials', ['id' => $memorial->id]);
});

it('keeps memorial images on soft delete and removes them on force delete', function (): void {
    Storage::fake();

    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();
    PetMemorialImage::factory()->create(['pet_memorial_id' => $memorial->id, 'path' => 'memorials/bella1.jpg']);
    PetMemorialImage::factory()->create(['pet_memorial_id' => $memorial->id, 'path' => 'memorials/bella2.jpg']);
    Storage::put('memorials/bella1.jpg', 'image');
    Storage::put('memorials/bella2.jpg', 'image');

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk();

    Storage::assertExists('memorials/bella1.jpg');
    Storage::assertExists('memorials/bella2.jpg');

    $memorial->forceDelete();

    Storage::assertMissing('memorials/bella1.jpg');
    Storage::assertMissing('memorials/bella2.jpg');
});

it('forbids another user from deleting a memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->deleteJson("/api/v1/memorials/{$memorial->slug}")
        ->assertForbidden();
});

it('rejects a pet passed date before the pet born date', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'body' => 'A loving companion.',
            'pet_name' => 'Rex',
            'pet_born_at' => '2020-01-01',
            'pet_passed_at' => '2019-01-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pet_passed_at');
});

it('rejects a pet passed date before the stored pet born date', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create([
        'pet_born_at' => '2020-01-01',
        'pet_passed_at' => '2024-01-01',
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", ['pet_passed_at' => '2019-01-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pet_passed_at');
});

it('accepts a pet passed date on or after the stored pet born date', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create([
        'pet_born_at' => '2020-01-01',
        'pet_passed_at' => '2024-01-01',
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", ['pet_passed_at' => '2023-06-01'])
        ->assertOk()
        ->assertJsonPath('data.pet_passed_at', '2023-06-01');
});

it('returns images array when showing a memorial', function (): void {
    $memorial = PetMemorial::factory()->create();
    $image = PetMemorialImage::factory()->create([
        'pet_memorial_id' => $memorial->id,
        'path' => 'memorials/test.jpg',
    ]);

    $this->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('data.images.0.id', $image->id)
        ->assertJsonPath('data.images.0.url', Storage::url('memorials/test.jpg'));
});

it('allows uploading additional images when updating a memorial', function (): void {
    Storage::fake();

    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();
    PetMemorialImage::factory()->create([
        'pet_memorial_id' => $memorial->id,
        'path' => 'memorials/existing.jpg',
    ]);

    $this->actingAs($owner, 'sanctum')
        ->post("/api/v1/memorials/{$memorial->slug}", [
            '_method' => 'PUT',
            'images' => [
                UploadedFile::fake()->image('new.jpg'),
            ],
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonCount(2, 'data.images');

    expect($memorial->refresh()->images)->toHaveCount(2);
});

it('updates a memorial using direct POST with multipart images', function (): void {
    Storage::fake();

    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($owner, 'sanctum')
        ->post("/api/v1/memorials/{$memorial->slug}", [
            'body' => 'Direct post update.',
            'images' => [
                UploadedFile::fake()->image('direct.jpg'),
            ],
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.body', 'Direct post update.')
        ->assertJsonCount(1, 'data.images');

    expect($memorial->refresh()->body)->toBe('Direct post update.')
        ->and($memorial->images)->toHaveCount(1);
});

it('optimizes uploaded memorial images to webp format without file size limits', function (): void {
    Storage::fake();

    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->post('/api/v1/memorials', [
            'body' => 'Optimized memory.',
            'pet_name' => 'Charlie',
            'images' => [
                UploadedFile::fake()->image('charlie.png', 800, 600),
            ],
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $memorial = PetMemorial::query()->where('pet_name', 'Charlie')->firstOrFail();
    $imagePath = $memorial->images->first()->path;

    expect(str_ends_with($imagePath, '.webp'))->toBeTrue();
    Storage::assertExists($imagePath);
});
