<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\Roles;
use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists only publicly visible memorials to a guest', function (): void {
    PetMemorial::factory()->count(2)->create();
    PetMemorial::factory()->draft()->create();
    PetMemorial::factory()->private()->create();

    $this->getJson('/api/v1/memorials')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.total', 2)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'pet_name',
                        'status',
                        'visibility',
                        'author' => ['id', 'name'],
                    ],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ],
        ]);
});

it('filters memorials by search', function (): void {
    PetMemorial::factory()->create(['title' => 'Remembering Rex', 'pet_name' => 'Rex']);
    PetMemorial::factory()->create(['title' => 'Remembering Bella', 'pet_name' => 'Bella']);

    $this->getJson('/api/v1/memorials?search=Rex')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.items.0.pet_name', 'Rex');
});

it('shows a public memorial to a guest and records the view', function (): void {
    $memorial = PetMemorial::factory()->create(['views_count' => 0]);

    $this->getJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $memorial->id)
        ->assertJsonPath('data.views_count', 1);

    expect($memorial->refresh()->views_count)->toBe(1);
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
            'title' => 'Goodbye Bella',
            'body' => 'A loving companion.',
            'pet_name' => 'Bella',
            'pet_type' => 'dog',
            'cover_image' => UploadedFile::fake()->image('bella.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Goodbye Bella')
        ->assertJsonPath('data.slug', 'goodbye-bella')
        ->assertJsonPath('data.pet_type', 'dog')
        ->assertJsonPath('data.status', PostStatus::Published->value)
        ->assertJsonPath('data.visibility', 'public');

    $this->assertDatabaseHas('pet_memorials', [
        'title' => 'Goodbye Bella',
        'user_id' => $user->id,
    ]);

    $memorial = PetMemorial::query()->where('title', 'Goodbye Bella')->firstOrFail();
    Storage::assertExists($memorial->cover_image);
});

it('rejects invalid input when creating a memorial', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'title' => '',
            'pet_type' => 'dragon',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['title', 'body', 'pet_name', 'pet_type'],
        ]);
});

it('lets the owner update their memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", [
            'title' => 'Updated Title',
            'visibility' => 'private',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.visibility', 'private');

    $this->assertDatabaseHas('pet_memorials', [
        'id' => $memorial->id,
        'title' => 'Updated Title',
    ]);
});

it('forbids another user from updating a memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", ['title' => 'Hijacked'])
        ->assertForbidden();
});

it('lets an admin update any memorial', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $memorial = PetMemorial::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/memorials/{$memorial->slug}", ['title' => 'Moderated'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Moderated');
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

it('keeps a memorial cover image on soft delete and removes it on force delete', function (): void {
    Storage::fake();

    $owner = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create(['cover_image' => 'covers/bella.jpg']);
    Storage::put('covers/bella.jpg', 'image');

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/memorials/{$memorial->slug}")
        ->assertOk();

    Storage::assertExists('covers/bella.jpg');

    $memorial->forceDelete();

    Storage::assertMissing('covers/bella.jpg');
});

it('forbids another user from deleting a memorial', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->deleteJson("/api/v1/memorials/{$memorial->slug}")
        ->assertForbidden();
});
