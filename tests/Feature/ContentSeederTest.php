<?php

declare(strict_types=1);

use App\Enums\FriendRequestStatus;
use App\Enums\StoryMediaType;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\FriendRequest;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\Story;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds content for every model', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(BlogCategory::count())->toBeGreaterThan(0)
        ->and(Tag::count())->toBeGreaterThan(0)
        ->and(BlogPost::count())->toBeGreaterThan(0)
        ->and(Product::count())->toBeGreaterThan(0)
        ->and(PetMemorial::count())->toBeGreaterThan(0)
        ->and(Comment::count())->toBeGreaterThan(0)
        ->and(Like::count())->toBeGreaterThan(0);
});

it('can run twice without duplicating records', function (): void {
    $this->seed(DatabaseSeeder::class);

    $counts = [
        'users' => User::count(),
        'categories' => BlogCategory::count(),
        'tags' => Tag::count(),
        'posts' => BlogPost::count(),
        'products' => Product::count(),
        'memorials' => PetMemorial::count(),
        'comments' => Comment::count(),
        'likes' => Like::count(),
        'friend_requests' => FriendRequest::withTrashed()->count(),
        'stories' => Story::count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect([
        'users' => User::count(),
        'categories' => BlogCategory::count(),
        'tags' => Tag::count(),
        'posts' => BlogPost::count(),
        'products' => Product::count(),
        'memorials' => PetMemorial::count(),
        'comments' => Comment::count(),
        'likes' => Like::count(),
        'friend_requests' => FriendRequest::withTrashed()->count(),
        'stories' => Story::count(),
    ])->toBe($counts);
});

it('files every post under a category with tags attached', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(BlogPost::query()->whereNull('blog_category_id')->exists())->toBeFalse()
        ->and(BlogPost::query()->doesntHave('tags')->exists())->toBeFalse();
});

it('seeds the published, draft and featured records the public listings expect', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(BlogPost::query()->published()->exists())->toBeTrue()
        ->and(BlogPost::query()->where('is_featured', true)->exists())->toBeTrue()
        ->and(Product::query()->published()->exists())->toBeTrue()
        ->and(PetMemorial::query()->publiclyVisible()->exists())->toBeTrue();
});

it('seeds comment threads with replies and likes on the listed content', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Comment::query()->whereNull('parent_id')->exists())->toBeTrue()
        ->and(Comment::query()->whereNotNull('parent_id')->exists())->toBeTrue()
        ->and(BlogPost::query()->has('comments')->exists())->toBeTrue()
        ->and(PetMemorial::query()->has('likes')->exists())->toBeTrue();
});

it('seeds a connected friend network with pending and rejected requests', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(FriendRequest::query()->where('status', FriendRequestStatus::Accepted)->exists())->toBeTrue()
        ->and(FriendRequest::query()->where('status', FriendRequestStatus::Pending)->exists())->toBeTrue()
        ->and(FriendRequest::query()->where('status', FriendRequestStatus::Rejected)->exists())->toBeTrue()
        ->and(FriendRequest::query()->where('status', FriendRequestStatus::Accepted)->distinct()->count('sender_id'))->toBeGreaterThan(1);
});

it('seeds active and expired stories with images and videos', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(StorySeeder::class);

    expect(Story::query()->where('expires_at', '>', now())->exists())->toBeTrue()
        ->and(Story::query()->where('expires_at', '<=', now())->exists())->toBeTrue()
        ->and(Story::query()->where('media_type', StoryMediaType::Image)->exists())->toBeTrue()
        ->and(Story::query()->where('media_type', StoryMediaType::Video)->exists())->toBeTrue();
});

it('leaves the admin account without any content', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@example.com')->sole();

    expect($admin->blogPosts()->count() + $admin->products()->count() + $admin->petMemorials()->count() + $admin->stories()->count())->toBe(0);
});
