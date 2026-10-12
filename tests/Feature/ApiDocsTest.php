<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('viewApiDocs', fn (?User $user = null): bool => true);
});

it('renders the API reference with the Scalar UI', function (): void {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('Scalar.createApiReference', escape: false);
});

it('groups the authentication endpoints under a single Auth tag', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect(array_column($response->json('tags'), 'name'))->toBe(['Auth'])
        ->and($response->json('paths./v1/auth/me.get.tags'))->toBe(['Auth'])
        ->and($response->json('paths./v1/auth/login.post.tags'))->toBe(['Auth'])
        ->and($response->json('paths./v1/auth/password/reset.post.tags'))->toBe(['Auth']);
});

it('documents a bearer token security scheme', function (): void {
    $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('components.securitySchemes.sanctum.type', 'http')
        ->assertJsonPath('components.securitySchemes.sanctum.scheme', 'bearer')
        ->assertJsonPath('components.securitySchemes.sanctum.bearerFormat', 'Sanctum')
        ->assertJsonPath('security.0.sanctum', []);
});

it('requires the bearer token for protected endpoints only', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    // Authenticated routes inherit the global bearer requirement.
    expect($response->json('paths./v1/auth/me.get.security'))->toBeNull()
        ->and($response->json('paths./v1/auth/logout.post.security'))->toBeNull();

    // Public routes opt out with an explicit empty security list.
    expect($response->json('paths./v1/auth/login.post.security'))->toBe([])
        ->and($response->json('paths./v1/auth/register.post.security'))->toBe([]);
});

it('documents the token returned by login and register', function (): void {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('paths./v1/auth/login.post.responses.200.content.application/json.schema.properties.data.properties.token.type'))
        ->toBe('string');
});
