<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PostStatus;
use App\Enums\VisibilityStatus;
use App\Models\PetMemorial;
use App\Models\User;

class PetMemorialPolicy
{
    /**
     * Anyone may browse the public memorial listing.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * A memorial is visible when it is public, or to its owner and admins.
     */
    public function view(?User $user, PetMemorial $memorial): bool
    {
        if ($this->owns($user, $memorial) || $user?->isAdmin() === true) {
            return true;
        }

        return $memorial->status === PostStatus::Published
            && $memorial->visibility === VisibilityStatus::Public;
    }

    /**
     * Any authenticated user may create a memorial.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner or an admin may change a memorial.
     */
    public function update(User $user, PetMemorial $memorial): bool
    {
        return $this->owns($user, $memorial) || $user->isAdmin();
    }

    /**
     * Only the owner or an admin may remove a memorial.
     */
    public function delete(User $user, PetMemorial $memorial): bool
    {
        return $this->owns($user, $memorial) || $user->isAdmin();
    }

    private function owns(?User $user, PetMemorial $memorial): bool
    {
        return $user instanceof User && $user->id === $memorial->user_id;
    }
}
