<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Roles;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $repository,
    ) {}

    public function getUsers(int $perPage = 15, ?string $search = null, ?Roles $role = null): LengthAwarePaginator
    {
        return $this->repository->getUsers($perPage, $search, $role);
    }

    public function find(int $id): User
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = isset($data['role']) ? Roles::from($data['role']) : Roles::User;
            unset($data['role']);

            $emailVerified = (bool) ($data['email_verified'] ?? true);
            unset($data['email_verified']);

            if (($data['avatar'] ?? null) instanceof UploadedFile) {
                $data['avatar'] = $data['avatar']->store('avatars');
            }

            $user = $this->repository->create($data);
            $user->setRole($role);

            if ($emailVerified) {
                $user->markEmailAsVerified();
            }

            return $this->repository->loadRoles($user);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $obsoleteAvatar = array_key_exists('avatar', $data) ? $user->avatar : null;

        $user = DB::transaction(function () use ($user, $data): User {
            if (isset($data['role'])) {
                $user->setRole(Roles::from($data['role']));
                unset($data['role']);
            }

            if (array_key_exists('email_verified', $data)) {
                if ($data['email_verified']) {
                    $user->markEmailAsVerified();
                } else {
                    $user->forceFill(['email_verified_at' => null])->save();
                }
                unset($data['email_verified']);
            }

            if (empty($data['password'])) {
                unset($data['password']);
            }

            if (array_key_exists('avatar', $data)) {
                $avatar = $data['avatar'];
                $data['avatar'] = $avatar instanceof UploadedFile ? $avatar->store('avatars') : null;
            }

            if ($data !== []) {
                $user = $this->repository->update($user, $data);
            }

            return $this->repository->loadRoles($user);
        });

        if ($obsoleteAvatar !== null) {
            Storage::delete($obsoleteAvatar);
        }

        return $user;
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        $avatar = $user->avatar;

        $this->repository->delete($user);

        if ($avatar !== null) {
            Storage::delete($avatar);
        }
    }
}
