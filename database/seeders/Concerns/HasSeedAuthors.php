<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

trait HasSeedAuthors
{
    /**
     * The regular (non-admin) users that own the seeded content.
     *
     * @return Collection<int, User>
     */
    protected function authors(): Collection
    {
        return User::query()
            ->with('roles')
            ->get()
            ->reject(fn (User $user): bool => $user->isAdmin())
            ->values();
    }
}
