---
paths:
  - 'config/**'
---

# Config

## The response cache needs a tag-capable store
The store is `redis` (`RESPONSE_CACHE_DRIVER`); tag invalidation requires a tag-capable store, and `file`/`database` silently break tag flushing.

Keep the package default `CacheAllSuccessfulGetRequests` profile — it appends the authenticated user id to the cache name, so each user gets isolated entries while guests share one. Do not add a custom cache profile. Leave `replacers` empty (API-only app, no CSRF tokens to strip) and keep `RESPONSE_CACHE_DEBUG` available for the `X-Cache-Status`/`X-Cache-Age`/`X-Cache-Key` headers.

## Image processing driver
Configure `config/images.php` to prefer `imagick` when available (`env('IMAGE_DRIVER', extension_loaded('imagick') ? 'imagick' : 'gd')`). The `imagick` driver processes image transformations outside PHP's Zend memory limit, preventing fatal memory exhaustion errors during large photo uploads.
