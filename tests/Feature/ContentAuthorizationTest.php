<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('resolves memorial visibility for guests, owners, and admins', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $public = PetMemorial::factory()->forUser($owner)->create();
    $private = PetMemorial::factory()->private()->forUser($owner)->create();

    expect(Gate::forUser(null)->allows('view', $public))->toBeTrue()
        ->and(Gate::forUser(null)->allows('view', $private))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('view', $private))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $private))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $private))->toBeFalse();
});

it('resolves memorial management abilities', function (): void {
    $owner = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $memorial = PetMemorial::factory()->forUser($owner)->create();

    expect(Gate::forUser($owner)->allows('update', $memorial))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $memorial))->toBeTrue()
        ->and(Gate::forUser($other)->allows('update', $memorial))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $memorial))->toBeTrue()
        ->and(Gate::forUser($other)->allows('delete', $memorial))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('create', PetMemorial::class))->toBeTrue();
});

it('restricts blog post creation to admins', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();

    expect(Gate::forUser($user)->allows('create', BlogPost::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', BlogPost::class))->toBeTrue();
});

it('hides draft blog posts from guests but not admins', function (): void {
    $draft = BlogPost::factory()->draft()->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();

    expect(Gate::forUser(null)->allows('view', $draft))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $draft))->toBeTrue();
});

it('restricts product management to admins and hides drafts from guests', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $draft = Product::factory()->draft()->create();
    $published = Product::factory()->create();

    expect(Gate::forUser($user)->allows('create', Product::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser(null)->allows('view', $draft))->toBeFalse()
        ->and(Gate::forUser(null)->allows('view', $published))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $draft))->toBeTrue();
});

it('restricts category management to admins', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $category = BlogCategory::factory()->create();

    expect(Gate::forUser($user)->allows('create', BlogCategory::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', BlogCategory::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $category))->toBeTrue();
});

it('resolves comment abilities for authors, other users, and admins', function (): void {
    $author = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $comment = Comment::factory()->forUser($author)->create();

    expect(Gate::forUser($author)->allows('create', Comment::class))->toBeTrue()
        ->and(Gate::forUser($author)->allows('delete', $comment))->toBeTrue()
        ->and(Gate::forUser($other)->allows('delete', $comment))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $comment))->toBeTrue()
        ->and(Gate::forUser($author)->allows('update', $comment))->toBeTrue()
        ->and(Gate::forUser($other)->allows('update', $comment))->toBeFalse();
});
