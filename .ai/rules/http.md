---
paths:
  - 'app/Http/**'
---

# Http

## Version the API under V1 namespaces
Namespace HTTP classes under V1: App\Http\Controllers\Api\V1, App\Http\Requests\Api\V1, App\Http\Resources\V1. A new API version gets sibling namespaces; never edit a shipped version in place.

Group a feature's controllers and Form Requests into a subfolder that matches its route file (routes/v1/{feature}.php), e.g. App\Http\Controllers\Api\V1\Auth and App\Http\Requests\Api\V1\Auth for the auth feature. API Resources stay directly under V1 because they can be shared across features.

## Example

```
app/Http/Controllers/Api/V1/Auth/AuthController.php -> App\Http\Controllers\Api\V1\Auth\AuthController
app/Http/Requests/Api/V1/Auth/LoginRequest.php       -> App\Http\Requests\Api\V1\Auth\LoginRequest
app/Http/Resources/V1/UserResource.php               -> App\Http\Resources\V1\UserResource
```

When V2 is needed, add `Api\V2` / `V2` siblings and mount them under a `v2` route prefix.
