---
paths:
  - 'tests/**'
---

# Tests

## Feature test every endpoint
Every endpoint gets a Pest feature test asserting the status code and the { success, message, data } envelope. Create data with model factories and use assertJsonStructure/assertJsonPath.

## Example

`tests/Feature/PostTest.php`

```php
use App\Models\Post;

it('lists posts', function (): void {
    Post::factory()->count(3)->create();

    $this->getJson('/api/v1/posts')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [['id', 'title', 'body']],
        ]);
});

it('creates a post', function (): void {
    $this->postJson('/api/v1/posts', [
        'title' => 'Hello',
        'body' => 'World',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('posts', ['title' => 'Hello']);
});

it('rejects invalid input with the error envelope', function (): void {
    $this->postJson('/api/v1/posts', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});
```
