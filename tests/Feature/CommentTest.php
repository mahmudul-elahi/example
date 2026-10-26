<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function (): void {
    it('requires authentication to list comments of a memorial', function (): void {
        $memorial = PetMemorial::factory()->create();

        $this->getJson("/api/v1/memorials/{$memorial->slug}/comments")
            ->assertUnauthorized();
    });

    it('lists top-level comments with their replies to an authenticated user', function (): void {
        $user = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->create();
        $comment = Comment::factory()->forCommentable($memorial)->create();
        Comment::factory()->forCommentable($memorial)->replyTo($comment)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/memorials/{$memorial->slug}/comments")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.per_page', 15)
            ->assertJsonPath('data.items.0.id', $comment->id)
            ->assertJsonPath('data.items.0.replies_count', 1)
            ->assertJsonCount(1, 'data.items.0.replies')
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'body',
                            'parent_id',
                            'author' => ['id', 'name'],
                            'replies' => [
                                '*' => ['id', 'body', 'parent_id'],
                            ],
                            'created_at',
                        ],
                    ],
                    'meta' => ['per_page', 'next_cursor', 'prev_cursor', 'has_next'],
                ],
            ]);
    });

    it('forbids an authenticated user from listing comments of another user\'s private memorial', function (): void {
        $user = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->private()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/memorials/{$memorial->slug}/comments")
            ->assertForbidden();
    });

    it('lets the owner list comments of their own private memorial', function (): void {
        $owner = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->private()->forUser($owner)->create();
        Comment::factory()->forCommentable($memorial)->create();

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/memorials/{$memorial->slug}/comments")
            ->assertOk()
            ->assertJsonPath('data.meta.per_page', 15);
    });
});

describe('store', function (): void {
    it('returns 401 when no token is provided', function (): void {
        $memorial = PetMemorial::factory()->create();

        $this->postJson("/api/v1/memorials/{$memorial->slug}/comments", ['body' => 'So sorry.'])
            ->assertUnauthorized();
    });

    it('creates a comment and returns it with the author', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/memorials/{$memorial->slug}/comments", ['body' => 'What a lovely tribute.'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'What a lovely tribute.')
            ->assertJsonPath('data.parent_id', null)
            ->assertJsonPath('data.author.id', $author->id);

        $this->assertDatabaseHas('comments', [
            'body' => 'What a lovely tribute.',
            'user_id' => $author->id,
            'commentable_type' => PetMemorial::class,
            'commentable_id' => $memorial->id,
        ]);
    });

    it('creates a reply to an existing comment', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->create();
        $parent = Comment::factory()->forCommentable($memorial)->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/memorials/{$memorial->slug}/comments", [
                'body' => 'Thinking of you.',
                'parent_id' => $parent->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    });

    it('returns 422 when the parent comment belongs to another memorial', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->create();
        $other = PetMemorial::factory()->create();
        $parent = Comment::factory()->forCommentable($other)->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/memorials/{$memorial->slug}/comments", [
                'body' => 'Wrong thread.',
                'parent_id' => $parent->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    });

    it('returns 422 when the parent comment is itself a reply', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->create();
        $parent = Comment::factory()->forCommentable($memorial)->create();
        $reply = Comment::factory()->forCommentable($memorial)->replyTo($parent)->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/memorials/{$memorial->slug}/comments", [
                'body' => 'Nested one level too deep.',
                'parent_id' => $reply->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        expect($memorial->comments()->count())->toBe(2);
    });

    it('returns 422 when the body is missing', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $memorial = PetMemorial::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/memorials/{$memorial->slug}/comments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    });
});

describe('destroy', function (): void {
    it('lets the author delete their own comment', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/comments/memorials/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    });

    it('forbids a non-author from deleting a comment', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $other = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/comments/memorials/{$comment->id}")
            ->assertForbidden();

        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
    });

    it('lets an admin delete any comment', function (): void {
        $admin = User::factory()->withRole(Roles::Admin)->create();
        $comment = Comment::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/comments/memorials/{$comment->id}")
            ->assertOk();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    });

    it('lets the author delete a comment on any content type', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $post = BlogPost::factory()->create();
        $comment = Comment::factory()->forUser($author)->forCommentable($post)->create();

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/comments/blog-posts/{$comment->id}")
            ->assertOk();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    });

    it('returns 404 when the comment belongs to another content type', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/comments/blog-posts/{$comment->id}")
            ->assertNotFound();

        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
    });

    it('returns 404 when the content type is unknown', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/comments/tags/{$comment->id}")
            ->assertNotFound();

        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
    });
});

describe('update', function (): void {
    it('lets the author update their own comment', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/comments/memorials/{$comment->id}", ['body' => 'Updated body.'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Updated body.');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Updated body.']);
    });

    it('forbids a non-author from updating a comment', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $other = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/v1/comments/memorials/{$comment->id}", ['body' => 'Hacked.'])
            ->assertForbidden();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id, 'body' => 'Hacked.']);
    });

    it('lets an admin update any comment', function (): void {
        $admin = User::factory()->withRole(Roles::Admin)->create();
        $comment = Comment::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/comments/memorials/{$comment->id}", ['body' => 'Admin edit.'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Admin edit.');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Admin edit.']);
    });

    it('lets the author update a comment on any content type', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $post = BlogPost::factory()->create();
        $comment = Comment::factory()->forUser($author)->forCommentable($post)->create();

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/comments/blog-posts/{$comment->id}", ['body' => 'Updated on blog.'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Updated on blog.');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Updated on blog.']);
    });

    it('returns 404 when the comment belongs to another content type', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/comments/blog-posts/{$comment->id}", ['body' => 'Wrong type.'])
            ->assertNotFound();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id, 'body' => 'Wrong type.']);
    });

    it('returns 404 when the content type is unknown', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/comments/tags/{$comment->id}", ['body' => 'Wrong type.'])
            ->assertNotFound();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id, 'body' => 'Wrong type.']);
    });

    it('returns 422 when the body is missing', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $comment = Comment::factory()->forUser($author)->create();

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/comments/memorials/{$comment->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    });
});

describe('blog posts', function (): void {
    it('lists the comments of a post to a guest', function (): void {
        $post = BlogPost::factory()->create();
        $comment = Comment::factory()->forCommentable($post)->create();
        Comment::factory()->forCommentable($post)->replyTo($comment)->create();

        $this->getJson("/api/v1/blog/{$post->slug}/comments")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.per_page', 15)
            ->assertJsonPath('data.items.0.id', $comment->id)
            ->assertJsonPath('data.items.0.replies_count', 1);
    });

    it('forbids a guest from listing the comments of a draft post', function (): void {
        $post = BlogPost::factory()->draft()->create();

        $this->getJson("/api/v1/blog/{$post->slug}/comments")
            ->assertForbidden();
    });

    it('creates a comment on a post and returns it with the author', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $post = BlogPost::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/blog/{$post->slug}/comments", ['body' => 'Great read.'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Great read.')
            ->assertJsonPath('data.author.id', $author->id);

        $this->assertDatabaseHas('comments', [
            'body' => 'Great read.',
            'user_id' => $author->id,
            'commentable_type' => BlogPost::class,
            'commentable_id' => $post->id,
        ]);
    });

    it('forbids a user from commenting on a draft post they cannot view', function (): void {
        $user = User::factory()->withRole(Roles::User)->create();
        $post = BlogPost::factory()->draft()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/blog/{$post->slug}/comments", ['body' => 'Not published yet.'])
            ->assertForbidden();

        expect($post->comments()->count())->toBe(0);
    });

    it('forbids a user from commenting on a post scheduled for the future', function (): void {
        $user = User::factory()->withRole(Roles::User)->create();
        $post = BlogPost::factory()->create(['published_at' => now()->addDay()]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/blog/{$post->slug}/comments", ['body' => 'Not released yet.'])
            ->assertForbidden();

        expect($post->comments()->count())->toBe(0);
    });
});

describe('products', function (): void {
    it('lists the comments of a product to a guest', function (): void {
        $product = Product::factory()->create();
        $comment = Comment::factory()->forCommentable($product)->create();

        $this->getJson("/api/v1/shop/{$product->slug}/comments")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.per_page', 15)
            ->assertJsonPath('data.items.0.id', $comment->id);
    });

    it('forbids a guest from listing the comments of a draft product', function (): void {
        $product = Product::factory()->draft()->create();

        $this->getJson("/api/v1/shop/{$product->slug}/comments")
            ->assertForbidden();
    });

    it('creates a comment on a product and returns it with the author', function (): void {
        $author = User::factory()->withRole(Roles::User)->create();
        $product = Product::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/shop/{$product->slug}/comments", ['body' => 'Exactly what I needed.'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Exactly what I needed.')
            ->assertJsonPath('data.author.id', $author->id);

        $this->assertDatabaseHas('comments', [
            'body' => 'Exactly what I needed.',
            'user_id' => $author->id,
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
        ]);
    });

    it('forbids a user from commenting on a draft product they cannot view', function (): void {
        $user = User::factory()->withRole(Roles::User)->create();
        $product = Product::factory()->draft()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shop/{$product->slug}/comments", ['body' => 'Not listed yet.'])
            ->assertForbidden();

        expect($product->comments()->count())->toBe(0);
    });
});

it('returns 422 when the parent comment belongs to another resource', function (): void {
    $author = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();
    $post = BlogPost::factory()->create();
    $parent = Comment::factory()->forCommentable($memorial)->create();

    $this->actingAs($author, 'sanctum')
        ->postJson("/api/v1/blog/{$post->slug}/comments", [
            'body' => 'Wrong thread.',
            'parent_id' => $parent->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');
});

it('does not expose the comment author email to a guest', function (): void {
    $post = BlogPost::factory()->create();
    Comment::factory()->forCommentable($post)->create();

    $this->getJson("/api/v1/blog/{$post->slug}/comments")
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

it('soft deletes the replies along with their comment', function (): void {
    $author = User::factory()->withRole(Roles::User)->create();
    $comment = Comment::factory()->forUser($author)->create();
    $reply = Comment::factory()->forUser($author)->replyTo($comment)->create();

    $this->actingAs($author, 'sanctum')
        ->deleteJson("/api/v1/comments/memorials/{$comment->id}")
        ->assertOk();

    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    $this->assertSoftDeleted('comments', ['id' => $reply->id]);
});
