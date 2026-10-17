---
paths:
  - 'app/Http/**'
---

# Http

## Version the API under V1 namespaces
Namespace HTTP classes under V1: App\Http\Controllers\Api\V1, App\Http\Requests\Api\V1, App\Http\Resources\V1. A new API version gets sibling namespaces; never edit a shipped version in place.

## Group controllers and requests by audience, matching the route file
Group each controller (and its Form Requests) by the route file that registers it, not by model. routes/api.php mounts four v1 files, and each has a matching subfolder:

| Route file | Middleware | Subfolder (controllers & Form Requests) |
| --- | --- | --- |
| routes/v1/auth.php | mixed, throttled | Api\V1\Auth |
| routes/v1/public.php | none | Api\V1\Public |
| routes/v1/user.php | auth:sanctum | Api\V1\User |
| routes/v1/admin.php | auth:sanctum + role:admin | Api\V1\Admin |

Guest-readable actions (`index`, `show`) live in `Public`; authenticated actions live in `User`. A controller that would hold both audiences is split into a `Public` and a `User` controller, each registered in the matching route file. API Resources stay directly under V1 because they can be shared across audiences.

## Example

```
app/Http/Controllers/Api/V1/Public/BlogPostController.php -> App\Http\Controllers\Api\V1\Public\BlogPostController
app/Http/Requests/Api/V1/Public/IndexBlogPostRequest.php  -> App\Http\Requests\Api\V1\Public\IndexBlogPostRequest
app/Http/Controllers/Api/V1/User/PetMemorialController.php -> App\Http\Controllers\Api\V1\User\PetMemorialController
app/Http/Requests/Api/V1/User/StorePetMemorialRequest.php  -> App\Http\Requests\Api\V1\User\StorePetMemorialRequest
app/Http/Resources/V1/UserResource.php                     -> App\Http\Resources\V1\UserResource
```

When V2 is needed, add `Api\V2` / `V2` siblings and mount them under a `v2` route prefix.
