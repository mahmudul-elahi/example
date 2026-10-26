<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;
use App\Repositories\BlockedUserRepositoryInterface;
use App\Repositories\FriendRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FriendRequestService implements FriendRequestServiceInterface
{
    private const int SUGGESTION_LIMIT = 5;

    public function __construct(
        protected FriendRequestRepositoryInterface $repository,
        protected BlockedUserRepositoryInterface $blockedUsers,
    ) {}

    public function send(User $sender, User $receiver): FriendRequest
    {
        if ($sender->is($receiver)) {
            throw ValidationException::withMessages([
                'user_id' => 'You cannot send a friend request to yourself.',
            ]);
        }

        if ($this->blockedUsers->isBlockedEitherWay($sender->id, $receiver->id)) {
            throw ValidationException::withMessages([
                'user_id' => 'You cannot send a friend request to this user.',
            ]);
        }

        return DB::transaction(function () use ($sender, $receiver): FriendRequest {
            $existing = $this->repository->findBetween($sender->id, $receiver->id);

            if ($existing instanceof FriendRequest) {
                if (! $existing->trashed()) {
                    if ($existing->status === FriendRequestStatus::Accepted) {
                        throw ValidationException::withMessages([
                            'user_id' => 'You are already friends with this user.',
                        ]);
                    }

                    if ($existing->status === FriendRequestStatus::Pending) {
                        throw ValidationException::withMessages([
                            'user_id' => 'A friend request between you and this user is already pending.',
                        ]);
                    }
                }

                // Reuse the pair's row after a rejection, cancellation or
                // unfriend so the unique sender/receiver pair is preserved.
                if ($existing->trashed()) {
                    $existing->restoreQuietly();
                }

                return $this->repository->update($existing, [
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'status' => FriendRequestStatus::Pending,
                    'responded_at' => null,
                ])->load(['sender', 'receiver']);
            }

            return $this->repository->create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'status' => FriendRequestStatus::Pending,
            ])->load(['sender', 'receiver']);
        });
    }

    public function accept(User $user, FriendRequest $friendRequest): FriendRequest
    {
        return $this->respond($user, $friendRequest, FriendRequestStatus::Accepted);
    }

    public function reject(User $user, FriendRequest $friendRequest): FriendRequest
    {
        return $this->respond($user, $friendRequest, FriendRequestStatus::Rejected);
    }

    public function cancel(User $user, FriendRequest $friendRequest): void
    {
        DB::transaction(function () use ($user, $friendRequest): void {
            $locked = $this->repository->findForUpdate($friendRequest->id);

            if ($locked->sender_id !== $user->id || $locked->status !== FriendRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'friend_request' => 'This friend request is no longer pending.',
                ]);
            }

            $this->repository->delete($locked);
        });
    }

    public function unfriend(User $user, User $friend): void
    {
        DB::transaction(function () use ($user, $friend): void {
            $friendship = $this->repository->findBetween($user->id, $friend->id);

            if (! $friendship instanceof FriendRequest
                || $friendship->trashed()
                || $friendship->status !== FriendRequestStatus::Accepted) {
                throw ValidationException::withMessages([
                    'user_id' => 'You are not friends with this user.',
                ]);
            }

            $this->repository->delete($friendship);
        });
    }

    public function received(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->repository->paginateReceived($user, $perPage, $cursor);
    }

    public function pendingCount(User $user): int
    {
        return $this->repository->countPendingReceived($user);
    }

    public function friends(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->repository->paginateFriends($user, $perPage, $cursor);
    }

    public function suggestions(User $user): Collection
    {
        return $this->repository->suggestions($user, self::SUGGESTION_LIMIT);
    }

    /**
     * Record the receiver's decision against a pending request.
     */
    private function respond(User $user, FriendRequest $friendRequest, FriendRequestStatus $status): FriendRequest
    {
        return DB::transaction(function () use ($user, $friendRequest, $status): FriendRequest {
            $locked = $this->repository->findForUpdate($friendRequest->id);

            if ($locked->receiver_id !== $user->id || $locked->status !== FriendRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'friend_request' => 'This friend request is no longer pending.',
                ]);
            }

            return $this->repository->update($locked, [
                'status' => $status,
                'responded_at' => now(),
            ])->load(['sender', 'receiver']);
        });
    }
}
