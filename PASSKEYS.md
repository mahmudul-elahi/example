# Passkeys (WebAuthn / FIDO2) Implementation Guide using `laravel/passkeys`

> [!IMPORTANT]
> **API-Only Scope**: This project is strictly a headless **REST API backend** built with Laravel 13, PHP 8.5, and Laravel Sanctum. No Blade views or full-stack web UIs are created here. All endpoints return standardized JSON via the `ApiResponse` trait and issue personal access tokens (Bearer tokens).

---

## 1. Executive Summary

This guide outlines how to implement passwordless **Passkeys (WebAuthn / FIDO2)** into this Laravel application using Laravel's official first-party package: **`laravel/passkeys`**.

Passkeys allow users to authenticate using biometric hardware (Apple Touch ID / Face ID, Windows Hello, Android Biometrics, or hardware keys like YubiKey) instead of passwords. In this API, successful passkey authentication exchanges a cryptographic signature for a standard **Laravel Sanctum Bearer Token**.

### Why `laravel/passkeys`?
* **Official First-Party Package**: Developed and maintained directly by the Laravel team.
* **Underlying Engine**: Uses the industry standard `web-auth/webauthn-lib 5.3` for low-level cryptographic verification (CBOR parsing, COSE keys, and signature validation).
* **100% Compatible**: Tested and fully compatible with PHP 8.5 and Laravel 13 in this repository.
* **Headless Friendly**: Can be integrated cleanly with our repository-service architecture and Sanctum token generation.

---

## 2. Architecture & Request Flow

```
┌────────────────────────────────────────────────────────┐
│              Client (SPA / Mobile App)                 │
│      Uses @laravel/passkeys or WebAuthn Browser API    │
└──────────────┬───────────────────────────▲─────────────┘
               │ 1. Request Challenge JSON │ 4. Sanctum Token
               │ 2. Submit Signed Payload  │
               ▼                           │
┌──────────────────────────────────────────┴─────────────┐
│          Laravel REST API Backend (v1/auth)            │
│                                                        │
│  [PasskeyController]                                   │
│         │                                              │
│         ▼                                              │
│  [PasskeyService] ──> Uses `laravel/passkeys` Engine   │
│         │                                              │
│         ▼                                              │
│  [PasskeyRepository] ──> [Database: passkeys]          │
└────────────────────────────────────────────────────────┘
```

---

## 3. Step-by-Step Backend Implementation

### Step 1: Install Package & Publish Migrations

Run in the project root:

```bash
# 1. Install the official first-party package
composer require laravel/passkeys

# 2. Publish package configuration and migrations
php artisan vendor:publish --tag=passkeys-migrations
php artisan vendor:publish --tag=passkeys-config

# 3. Run migrations to create the `passkeys` table
php artisan migrate
```

The published migration creates the `passkeys` table:
* `id`: Primary key
* `user_id`: Foreign key linked to `users.id` (cascade delete)
* `name`: Friendly device name (e.g. *"MacBook Pro Touch ID"*, *"iPhone 16 Face ID"*)
* `credential_id`: Unique public credential identifier (indexed)
* `public_key`: COSE-encoded public key
* `counter`: Signature counter (tracks usage to detect and block cloned authenticators)
* `aaguid`: Authenticator identification GUID
* `transports`: Transport capabilities (JSON: `["internal", "hybrid", "usb"]`)
* `last_used_at`: Timestamp of the last successful authentication

---

### Step 2: Configure Environment & `config/passkeys.php`

Ensure `config/passkeys.php` references your application's domain and relying party name:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Relying Party Details
    |--------------------------------------------------------------------------
    */
    'relying_party' => [
        'name' => env('PASSKEY_RP_NAME', config('app.name', 'Example App')),
        'id' => env('PASSKEY_RP_ID', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        'icon' => env('PASSKEY_RP_ICON'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout (Milliseconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => 60000, // 60 seconds
];
```

In `.env`:
```env
PASSKEY_RP_NAME="Pet Memorials API"
PASSKEY_RP_ID="localhost" # In production: "yourdomain.com"
```

---

### Step 3: Update `App\Models\User`

Implement the `PasskeyUser` contract and add the `PasskeyAuthenticatable` trait in [app/Models/User.php](file:///home/mahmudul/Desktop/example/app/Models/User.php):

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable;

    // Optional overrides if you customize the credential display attributes:
    public function getPasskeyDisplayName(): string
    {
        return $this->name;
    }

    public function getPasskeyUsername(): string
    {
        return $this->email;
    }
}
```

---

### Step 4: Repository Layer (`PasskeyRepository`)

Create [app/Repositories/PasskeyRepositoryInterface.php](file:///home/mahmudul/Desktop/example/app/Repositories/PasskeyRepositoryInterface.php) and its Eloquent implementation:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Passkeys\Models\Passkey;

interface PasskeyRepositoryInterface
{
    /**
     * @return Collection<int, Passkey>
     */
    public function getForUser(User $user): Collection;

    public function findForUser(User $user, int $id): ?Passkey;

    public function findByCredentialId(string $credentialId): ?Passkey;

    public function rename(Passkey $passkey, string $newName): Passkey;

    public function delete(Passkey $passkey): void;
}
```

---

### Step 5: Service Layer (`PasskeyService`)

Create [app/Services/PasskeyServiceInterface.php](file:///home/mahmudul/Desktop/example/app/Services/PasskeyServiceInterface.php) and [app/Services/PasskeyService.php](file:///home/mahmudul/Desktop/example/app/Services/PasskeyService.php):

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Passkeys\Models\Passkey;

interface PasskeyServiceInterface
{
    /**
     * Generate WebAuthn creation options for registering a new passkey.
     *
     * @return array<string, mixed>
     */
    public function generateRegisterOptions(User $user): array;

    /**
     * Verify attestation response and save the passkey.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyAndRegister(User $user, array $data): Passkey;

    /**
     * Generate assertion challenge options for login.
     *
     * @return array{options: array<string, mixed>, challenge_token: string}
     */
    public function generateLoginOptions(?string $email = null): array;

    /**
     * Verify assertion signature and return user + Sanctum token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function verifyAndLogin(array $data): array;

    /**
     * @return Collection<int, Passkey>
     */
    public function listUserPasskeys(User $user): Collection;

    public function renamePasskey(User $user, int $id, string $newName): Passkey;

    public function deletePasskey(User $user, int $id): void;
}
```

#### How the Service verifies and issues Sanctum tokens:
```php
public function verifyAndLogin(array $data): array
{
    // 1. Retrieve challenge from Cache using challenge_token
    $challenge = Cache::pull('passkey:login:'.$data['challenge_token']);
    if (! $challenge) {
        throw ValidationException::withMessages(['passkey' => 'Authentication challenge expired or invalid.']);
    }

    // 2. Validate cryptographic signature using laravel/passkeys validator
    $user = $this->passkeyAuthenticator->authenticate($data['credential'], $challenge);

    // 3. Issue standard Laravel Sanctum Bearer token
    return [
        'user' => $this->userRepository->loadRoles($user),
        'token' => $user->createToken('auth-token')->plainTextToken,
    ];
}
```

---

### Step 6: API Resources & Form Requests

#### 1. PasskeyResource
Transform passkey records safely (avoiding exposing public key binaries in responses):

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passkeys\Models\Passkey;

/**
 * @mixin Passkey
 */
class PasskeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

#### 2. Form Requests
* `PasskeyRegisterVerifyRequest`: validates `name` (`required|string|max:100`), `credential` (`required|array`).
* `PasskeyLoginVerifyRequest`: validates `challenge_token` (`required|string`), `credential` (`required|array`).
* `PasskeyUpdateRequest`: validates `name` (`required|string|max:100`).

---

### Step 7: Controller (`PasskeyController`)

Create [app/Http/Controllers/Api/V1/Auth/PasskeyController.php](file:///home/mahmudul/Desktop/example/app/Http/Controllers/Api/V1/Auth/PasskeyController.php) with Scramble annotations and the `ApiResponse` trait:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\PasskeyLoginVerifyRequest;
use App\Http\Requests\Api\V1\Auth\PasskeyRegisterVerifyRequest;
use App\Http\Requests\Api\V1\Auth\PasskeyUpdateRequest;
use App\Http\Resources\V1\PasskeyResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\PasskeyServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Auth', weight: 1)]
class PasskeyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasskeyServiceInterface $passkeys,
    ) {}

    // GET /api/v1/auth/passkeys/register/options [auth:sanctum]
    public function registerOptions(#[CurrentUser] User $user): JsonResponse
    {
        $options = $this->passkeys->generateRegisterOptions($user);

        return $this->success($options, 'Passkey creation options generated');
    }

    // POST /api/v1/auth/passkeys/register/verify [auth:sanctum]
    public function registerVerify(PasskeyRegisterVerifyRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $passkey = $this->passkeys->verifyAndRegister($user, $request->validated());

        return $this->created(new PasskeyResource($passkey), 'Passkey registered successfully');
    }

    // POST /api/v1/auth/passkeys/login/options [public, throttled]
    public function loginOptions(Request $request): JsonResponse
    {
        $options = $this->passkeys->generateLoginOptions($request->input('email'));

        return $this->success($options, 'Passkey login options generated');
    }

    // POST /api/v1/auth/passkeys/login/verify [public, throttled]
    public function loginVerify(PasskeyLoginVerifyRequest $request): JsonResponse
    {
        $result = $this->passkeys->verifyAndLogin($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully');
    }

    // GET /api/v1/auth/passkeys [auth:sanctum]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $passkeys = $this->passkeys->listUserPasskeys($user);

        return $this->success(PasskeyResource::collection($passkeys));
    }

    // PATCH /api/v1/auth/passkeys/{id} [auth:sanctum]
    public function update(PasskeyUpdateRequest $request, #[CurrentUser] User $user, int $id): JsonResponse
    {
        $passkey = $this->passkeys->renamePasskey($user, $id, $request->validated('name'));

        return $this->success(new PasskeyResource($passkey), 'Passkey updated successfully');
    }

    // DELETE /api/v1/auth/passkeys/{id} [auth:sanctum]
    public function destroy(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->passkeys->deletePasskey($user, $id);

        return $this->success(null, 'Passkey deleted successfully');
    }
}
```

---

### Step 8: Route Definitions

In [routes/v1/auth.php](file:///home/mahmudul/Desktop/example/routes/v1/auth.php):

```php
Route::prefix('auth')->group(function (): void {
    // Existing password & OTP routes...

    // Passkey Login (Public)
    Route::prefix('passkeys')->group(function (): void {
        Route::post('login/options', [PasskeyController::class, 'loginOptions'])
            ->middleware('throttle:login');
        Route::post('login/verify', [PasskeyController::class, 'loginVerify'])
            ->middleware('throttle:login');
    });

    // Authenticated Passkey Endpoints
    Route::middleware('auth:sanctum')->prefix('passkeys')->group(function (): void {
        Route::get('register/options', [PasskeyController::class, 'registerOptions']);
        Route::post('register/verify', [PasskeyController::class, 'registerVerify']);
        Route::get('/', [PasskeyController::class, 'index']);
        Route::patch('{id}', [PasskeyController::class, 'update']);
        Route::delete('{id}', [PasskeyController::class, 'destroy']);
    });
});
```

---

## 4. Frontend Implementation Guide (Step-by-Step)

This guide covers everything required to implement Passkeys in your frontend application (React, Next.js, Vue, Nuxt, Svelte, or Vanilla JS/TypeScript).

---

### Step 1: Install a WebAuthn Client Helper

Browsers provide native `navigator.credentials.create()` and `navigator.credentials.get()`, but these APIs work with raw `ArrayBuffer` binaries. We strongly recommend using either **`@laravel/passkeys`** or **`@simplewebauthn/browser`** which automatically handle the Base64URL encoding/decoding.

```bash
# Option A: Laravel's official client helper
npm install @laravel/passkeys

# Option B: Industry-standard SimpleWebAuthn client
npm install @simplewebauthn/browser
```

> [!TIP]
> Both packages work identically with our API. Below examples use `@laravel/passkeys` and `@simplewebauthn/browser` interchangeably.

---

### Step 2: Feature Detection & Browser Support

Before showing Passkey UI elements (like a "Sign in with Passkey" button or Touch ID icon), check if the user's device supports biometrics:

```typescript
// passkeys.ts
export async function isPasskeySupported(): Promise<boolean> {
  return (
    window.PublicKeyCredential !== undefined &&
    typeof window.PublicKeyCredential === 'function' &&
    await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
  );
}

// Check if browser supports Autofill (Conditional UI)
export async function isAutofillSupported(): Promise<boolean> {
  return (
    window.PublicKeyCredential !== undefined &&
    typeof window.PublicKeyCredential.isConditionalMediationAvailable === 'function' &&
    await PublicKeyCredential.isConditionalMediationAvailable()
  );
}
```

* If `isPasskeySupported()` is `false`, hide the biometric login button or show a helpful message.

---

### Step 3: Passwordless Login Implementation

#### Flow A: 1-Click "Sign in with Passkey" Button

When the user taps **"Sign in with Passkey"** (no email required):

```typescript
import { Passkeys } from '@laravel/passkeys';
// or: import { startAuthentication } from '@simplewebauthn/browser';

export async function loginWithPasskey(): Promise<{ user: any; token: string }> {
  // 1. Request options challenge from our API (empty body)
  const optionsResponse = await fetch('/api/v1/auth/passkeys/login/options', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({}),
  });

  if (!optionsResponse.ok) {
    throw new Error('Failed to fetch passkey login options');
  }

  const { data } = await optionsResponse.json();
  const { challenge_token, options } = data;

  // 2. Trigger the browser's biometric prompt (Touch ID / Face ID / Windows Hello)
  let credential;
  try {
    credential = await Passkeys.get(options);
    // Alternatively using @simplewebauthn/browser:
    // credential = await startAuthentication({ optionsJSON: options });
  } catch (error: any) {
    if (error.name === 'NotAllowedError') {
      throw new Error('Passkey sign-in was canceled.');
    }
    throw error;
  }

  // 3. Send the signed credential back to the API for verification
  const verifyResponse = await fetch('/api/v1/auth/passkeys/login/verify', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      challenge_token,
      credential,
    }),
  });

  const verifyResult = await verifyResponse.json();

  if (!verifyResponse.ok || !verifyResult.success) {
    throw new Error(verifyResult.message || 'Passkey verification failed');
  }

  // 4. Save the Sanctum Bearer token for future authenticated requests
  const { user, token } = verifyResult.data;
  localStorage.setItem('auth_token', token);

  return { user, token };
}
```

---

#### Flow B: Browser Autofill (Conditional UI)

Autofill allows users to click the email/username field and instantly select their passkey from the browser's password manager dropdown.

1. In your HTML input, add `autocomplete="username webauthn"`:
   ```html
   <input
     type="email"
     id="email"
     name="email"
     autocomplete="username webauthn"
     placeholder="Email address"
   />
   ```

2. When the login page mounts, start conditional authentication in the background:
   ```typescript
   import { startAuthentication } from '@simplewebauthn/browser';

   export async function initPasskeyAutofill(onSuccess: (data: any) => void) {
     if (!(await isAutofillSupported())) return;

     try {
       // Fetch options
       const res = await fetch('/api/v1/auth/passkeys/login/options', { method: 'POST' });
       const { data } = await res.json();

       // Start conditional authentication (non-blocking)
       const credential = await startAuthentication({
         optionsJSON: data.options,
         useBrowserAutofill: true,
       });

       // Send to verify
       const verifyRes = await fetch('/api/v1/auth/passkeys/login/verify', {
         method: 'POST',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify({
           challenge_token: data.challenge_token,
           credential,
         }),
       });

       const result = await verifyRes.json();
       if (result.success) {
         localStorage.setItem('auth_token', result.data.token);
         onSuccess(result.data);
       }
     } catch (err) {
       // Autofill will abort quietly if the user types a normal password instead
     }
   }
   ```

---

### Step 4: Register a New Passkey (Settings / Security)

When a logged-in user clicks **"Add Passkey"** in their Profile or Security Settings:

```typescript
import { Passkeys } from '@laravel/passkeys';
// or: import { startRegistration } from '@simplewebauthn/browser';

export async function registerNewPasskey(deviceName?: string): Promise<any> {
  const token = localStorage.getItem('auth_token');
  if (!token) throw new Error('User is not authenticated');

  // Auto-detect a friendly device label if none provided
  const defaultName = deviceName || getDefaultDeviceName();

  // 1. Request registration creation options from the API
  const optionsResponse = await fetch('/api/v1/auth/passkeys/register/options', {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
  });

  if (!optionsResponse.ok) {
    throw new Error('Failed to generate passkey registration options');
  }

  const { data: options } = await optionsResponse.json();

  // 2. Perform WebAuthn registration on the device
  let credential;
  try {
    credential = await Passkeys.create(options);
    // Alternatively using @simplewebauthn/browser:
    // credential = await startRegistration({ optionsJSON: options });
  } catch (error: any) {
    if (error.name === 'NotAllowedError') {
      throw new Error('Passkey registration was canceled.');
    }
    if (error.name === 'InvalidStateError') {
      throw new Error('This passkey is already registered to your account.');
    }
    throw error;
  }

  // 3. Send the public credential back to the API
  const saveResponse = await fetch('/api/v1/auth/passkeys/register/verify', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({
      name: defaultName,
      credential,
    }),
  });

  const saveResult = await saveResponse.json();

  if (!saveResponse.ok || !saveResult.success) {
    throw new Error(saveResult.message || 'Failed to save passkey');
  }

  return saveResult.data; // Newly created PasskeyResource
}

// Helper to auto-name the device
function getDefaultDeviceName(): string {
  const ua = navigator.userAgent;
  if (/iPhone|iPad/.test(ua)) return 'Apple iOS Device';
  if (/Macintosh/.test(ua)) return 'MacBook / Mac';
  if (/Windows/.test(ua)) return 'Windows Hello PC';
  if (/Android/.test(ua)) return 'Android Device';
  return 'Personal Device';
}
```

---

### Step 5: Managing Existing Passkeys (List, Rename, Delete)

In the user's **Security Settings** page:

```typescript
const API_URL = '/api/v1/auth/passkeys';
const authHeaders = () => ({
  'Accept': 'application/json',
  'Content-Type': 'application/json',
  'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
});

// 1. List user's registered passkeys
export async function getPasskeys() {
  const res = await fetch(API_URL, { headers: authHeaders() });
  const result = await res.json();
  return result.data; // Array of PasskeyResource: [{ id, name, authenticator, last_used_at, created_at }]
}

// 2. Rename a passkey
export async function renamePasskey(id: number, newName: string) {
  const res = await fetch(`${API_URL}/${id}`, {
    method: 'PATCH',
    headers: authHeaders(),
    body: JSON.stringify({ name: newName }),
  });
  const result = await res.json();
  return result.data;
}

// 3. Revoke / Delete a passkey
export async function deletePasskey(id: number) {
  const res = await fetch(`${API_URL}/${id}`, {
    method: 'DELETE',
    headers: authHeaders(),
  });
  return res.ok;
}
```

---

### Step 6: Full React / Next.js Component Example

Here is a ready-to-use React component for a clean login button:

```tsx
import React, { useState, useEffect } from 'react';
import { isPasskeySupported, loginWithPasskey } from './passkeys';

export function PasskeyLoginButton({ onLoginSuccess }: { onLoginSuccess: (user: any) => void }) {
  const [supported, setSupported] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    isPasskeySupported().then(setSupported);
  }, []);

  if (!supported) return null; // Don't show button if device doesn't support biometrics

  const handleLogin = async () => {
    setLoading(true);
    setError(null);
    try {
      const { user } = await loginWithPasskey();
      onLoginSuccess(user);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="passkey-login-wrapper">
      <button
        type="button"
        onClick={handleLogin}
        disabled={loading}
        className="flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-lg border border-gray-300 font-medium text-gray-700 hover:bg-gray-50 transition"
      >
        <svg className="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004.07 9.29"/>
        </svg>
        {loading ? 'Authenticating with device...' : 'Sign in with Passkey'}
      </button>

      {error && <p className="text-sm text-red-600 mt-2">{error}</p>}
    </div>
  );
}
```

---

### Step 7: Vue 3 / Nuxt 3 Composable Example

```typescript
// composables/usePasskey.ts
import { ref, onMounted } from 'vue';
import { isPasskeySupported, loginWithPasskey, registerNewPasskey } from '~/utils/passkeys';

export function usePasskey() {
  const isSupported = ref(false);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  onMounted(async () => {
    isSupported.value = await isPasskeySupported();
  });

  const login = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      return await loginWithPasskey();
    } catch (err: any) {
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const register = async (name?: string) => {
    isLoading.value = true;
    error.value = null;
    try {
      return await registerNewPasskey(name);
    } catch (err: any) {
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  return { isSupported, isLoading, error, login, register };
}
```

---

### Step 8: Common Frontend Errors & Handling

| Error Name / Case | Meaning | Recommended UI Reaction |
| :--- | :--- | :--- |
| `NotAllowedError` | The user pressed "Cancel" or Face ID / Touch ID timed out. | Ignore quietly or show a subtle note (*"Passkey prompt was canceled"*). Do not show a red failure banner. |
| `InvalidStateError` | During registration: this exact device/authenticator is already registered. | Show friendly alert: *"This passkey is already added to your account."* |
| `SecurityError` | The application is running on an insecure HTTP context (not `https://` or `localhost`). | Ensure local dev runs on `localhost` or an HTTPS proxy. |
| HTTP `422 Unprocessable Entity` | The 2-minute challenge token expired. | Prompt the user to tap "Sign in with Passkey" again to obtain a fresh challenge. |

---

## 5. Security & Edge Case Handling

1. **Replay Protection**: Challenges are cryptographically random strings stored with a short TTL (120 seconds). When verified, `Cache::pull()` is used so a challenge can never be submitted twice.
2. **Authenticator Counter Validation**: `laravel/passkeys` tracks the integer signature counter. If the counter presented is less than or equal to the stored counter, the request is rejected as a cloned authenticator threat.
3. **Phishing Protection (Relying Party ID)**: The browser hardware checks that the current domain matches the `rpId` before producing the signature. Attackers cannot use credentials signed for other domains.
4. **Fallback Mechanism**: Passkeys do not replace passwords or email OTPs; they complement them. If a user loses their phone or hardware key, they can still log in using standard password/email verification.

---

## 6. Testing Strategy with Pest 5

Create `tests/Feature/Api/V1/Auth/PasskeyTest.php`:

```php
it('generates passkey registration options for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/auth/passkeys/register/options');

    $response->assertOk()
        ->assertJsonStructure(['status', 'data' => ['challenge', 'rp', 'user']]);
});

it('rejects login verification with expired challenge token', function () {
    $response = $this->postJson('/api/v1/auth/passkeys/login/verify', [
        'challenge_token' => 'non-existent-or-expired',
        'credential' => ['id' => 'fake'],
    ]);

    $response->assertStatus(422);
});

it('allows user to list, rename, and delete passkeys', function () {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    // Rename
    $this->actingAs($user)
        ->patchJson("/api/v1/auth/passkeys/{$passkey->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    // Delete
    $this->actingAs($user)
        ->deleteJson("/api/v1/auth/passkeys/{$passkey->id}")
        ->assertOk();

    expect(Passkey::find($passkey->id))->toBeNull();
});
```
