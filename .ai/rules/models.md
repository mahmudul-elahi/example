---
paths:
  - 'app/Models/**'
---

# Models

## Model and migration conventions
Models live in app/Models (singular, StudlyCase) with $fillable, $casts(), and typed relationship methods. Migrations use plural snake_case tables, foreignId()->constrained(), and indexes on foreign/filtered columns. No queries in controllers or resources.

## Example

Model — `app/Models/Post.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['title', 'body', 'user_id', 'published_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Migration:

```php
Schema::create('posts', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('body');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();

    $table->index('published_at');
});
```

## Users have exactly one role
The User model uses Spatie's `HasRoles`, but a user holds at most one role — `Roles::Admin` or `Roles::User` (`.ai/rules/enums.md`). Assign with `setRole(Roles $role)`, which syncs (replaces) the pivot so it never holds more than one row. Never call the multi-role API (`assignRole` with several roles, `syncRoles` with a list) on User.

Read the role name as a string with `$user->role` (`null` when unassigned), or use `$user->isAdmin()`. Route protection uses the `role` middleware alias (see `.ai/rules/routes.md`); permission checks use `$user->can()`.

This is an API-only application: the default auth guard is `sanctum`, so roles and permission checks are stored and matched under the `sanctum` guard. Do not introduce a second guard for roles.
