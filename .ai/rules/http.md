---
paths:
  - 'app/Http/**'
---

# Http

## Version the API under V1 namespaces
Namespace HTTP classes under V1: App\Http\Controllers\Api\V1, App\Http\Requests\Api\V1, App\Http\Resources\V1. A new API version gets sibling namespaces; never edit a shipped version in place.

## Example

```
app/Http/Controllers/Api/V1/PostController.php   -> App\Http\Controllers\Api\V1\PostController
app/Http/Requests/Api/V1/StorePostRequest.php    -> App\Http\Requests\Api\V1\StorePostRequest
app/Http/Resources/V1/PostResource.php           -> App\Http\Resources\V1\PostResource
```

When V2 is needed, add `Api\V2` / `V2` siblings and mount them under a `v2` route prefix.
