<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

class UserSessionService implements UserSessionServiceInterface
{
    /**
     * @return Collection<int, PersonalAccessToken>
     */
    public function listSessions(User $user): Collection
    {
        return $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function revokeSession(User $user, int $sessionId): void
    {
        $token = $user->tokens()->where('id', $sessionId)->first();

        abort_if(! $token instanceof PersonalAccessToken, 404, 'Session not found');

        $token->delete();
    }

    public function revokeOtherSessions(User $user): void
    {
        $currentTokenId = $user->currentAccessToken()?->id;

        if ($currentTokenId !== null) {
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        } else {
            $user->tokens()->delete();
        }
    }
}
