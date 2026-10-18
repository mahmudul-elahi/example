---
paths:
  - 'app/Http/Controllers/Api/V1/Public/**'
---

# Public

## Tag cached public endpoints with ResponseCacheTag
Cache public GET endpoints with the `#[Cache]` attribute and tag them through `App\Enums\ResponseCacheTag`; never write tag strings.

```php
#[Cache(lifetime: 600, tags: [ResponseCacheTag::Blog->value])]
public function index(IndexBlogPostRequest $request): JsonResponse
```

Attribute arguments must be constant expressions, so pass the enum case's `->value`; the `ResponseCacheTag::values()` helper is only usable in runtime code (observers, route files). Put endpoints that must run on every request (show routes that record a view, login) behind `#[NoCache]`.
