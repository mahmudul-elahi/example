---
paths:
  - 'app/Observers/**'
---

# Observers

## Flush the response cache by tag through the trait
Observers invalidate through `App\Traits\FlushesResponseCache`, passing `ResponseCacheTag` cases. Never call `ResponseCache::clear()` or `forget()` directly, and never hardcode tag strings.

```php
use App\Traits\FlushesResponseCache;

class BlogPostObserver
{
    use FlushesResponseCache;

    private function invalidate(BlogPost $blogPost): void
    {
        $this->flushResponseCache(ResponseCacheTag::Blog);
    }
}
```

Tag-based flushing clears every variant of a cached endpoint (pagination, search, per-user), while URL-based `forget()` silently misses them. Define handlers only for events the observed model actually fires.
