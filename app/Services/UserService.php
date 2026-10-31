<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Roles;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $repository,
        protected EmailVerificationServiceInterface $emailVerification,
    ) {}

    public function getUsers(int $perPage = 15, ?string $search = null, ?Roles $role = null): LengthAwarePaginator
    {
        return $this->repository->getUsers($perPage, $search, $role);
    }

    public function find(int $id): User
    {
        return $this->repository->find($id);
    }

    public function getProfile(User $user, ?User $viewer = null): User
    {
        return $this->repository->getProfile($user, $viewer);
    }

    /**
     * @return array{memorials_count: int, likes_count: int, comments_count: int}
     */
    public function getStats(User $user): array
    {
        return $this->repository->getStats($user);
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
                $path = Image::fromUpload($data['avatar'])
                    ->scale(width: 1024)
                    ->optimize()
                    ->store('avatars');
                $data['avatar'] = $path !== false ? $path : null;
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
        $emailChanged = isset($data['email']) && $data['email'] !== $user->email;
        $emailVerifiedProvided = array_key_exists('email_verified', $data);

        $user = DB::transaction(function () use ($user, $data, $emailChanged, $emailVerifiedProvided): User {
            if (isset($data['role'])) {
                $role = Roles::from($data['role']);

                if ($user->isAdmin() && $role !== Roles::Admin && ! $this->repository->hasOtherAdmin($user)) {
                    throw ValidationException::withMessages([
                        'role' => 'The last admin cannot be demoted.',
                    ]);
                }

                $user->setRole($role);
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

            $passwordChanged = ! empty($data['password']);

            if (! $passwordChanged) {
                unset($data['password']);
            }

            if (array_key_exists('avatar', $data)) {
                $avatar = $data['avatar'];
                $path = $avatar instanceof UploadedFile
                    ? Image::fromUpload($avatar)->scale(width: 1024)->optimize()->store('avatars')
                    : null;
                $data['avatar'] = $path !== false ? $path : null;
            }

            if ($data !== []) {
                $user = $this->repository->update($user, $data);
            }

            if ($emailChanged && ! $emailVerifiedProvided) {
                $user->forceFill(['email_verified_at' => null])->save();
            }

            if ($passwordChanged) {
                $user->tokens()->delete();
            }

            return $this->repository->loadRoles($user);
        });

        if ($obsoleteAvatar !== null) {
            Storage::delete($obsoleteAvatar);
        }

        return $user;
    }

    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        return $this->update($user, [
            'avatar' => $avatar,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $filtered = array_intersect_key($data, array_flip([
            'name',
            'username',
            'email',
            'date_of_birth',
            'phone_number',
            'location',
        ]));
        $emailChanged = isset($filtered['email']) && $filtered['email'] !== $user->email;

        $updatedUser = $this->update($user, $filtered);

        if ($emailChanged) {
            $this->emailVerification->sendOtp($updatedUser);
        }

        return $updatedUser;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword): void {
            $this->repository->updatePassword($user, $newPassword);

            $currentTokenId = $user->currentAccessToken()?->id;
            if ($currentTokenId !== null) {
                $user->tokens()->where('id', '!=', $currentTokenId)->delete();
            }
        });
    }

    public function updatePreferences(User $user, array $preferences): User
    {
        $current = $user->resolvedPreferences();
        unset($current['privacy'], $preferences['privacy']);
        $merged = array_replace_recursive($current, $preferences);
        unset($merged['privacy']);

        $user->forceFill(['preferences' => $merged])->save();

        return $user;
    }

    public function deleteAccount(User $user): void
    {
        if ($user->isAdmin() && ! $this->repository->hasOtherAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => ['The last administrator account cannot be deleted.'],
            ]);
        }

        $files = array_values(array_filter([
            $user->avatar,
            ...$this->repository->authoredMediaFiles($user),
        ]));

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->notifications()->delete();
            $this->repository->delete($user);
        });

        foreach ($files as $file) {
            Storage::delete($file);
        }
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        if ($user->isAdmin() && ! $this->repository->hasOtherAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => ['The last admin cannot be deleted.'],
            ]);
        }

        $files = array_values(array_filter([
            $user->avatar,
            ...$this->repository->authoredMediaFiles($user),
        ]));

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->notifications()->delete();
            $this->repository->delete($user);
        });

        if ($files !== []) {
            Storage::delete($files);
        }
    }
}
