<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;
use App\Repositories\FriendRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FriendRequestService implements FriendRequestServiceInterface
{
    public function __construct(
        protected FriendRequestRepositoryInterface $repository,
    ) {}

    public function send(User $sender, User $receiver): FriendRequest
    {
        if ($sender->is($receiver)) {
            throw ValidationException::withMessages([
                'user_id' => 'You cannot send a friend request to yourself.',
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
        return $this->respond($friendRequest, FriendRequestStatus::Accepted);
    }

    public function reject(User $user, FriendRequest $friendRequest): FriendRequest
    {
        return $this->respond($friendRequest, FriendRequestStatus::Rejected);
    }

    public function cancel(User $user, FriendRequest $friendRequest): void
    {
        $this->repository->delete($friendRequest);
    }

    public function unfriend(User $user, User $friend): void
    {
        $friendship = $this->repository->findBetween($user->id, $friend->id);

        if (! $friendship instanceof FriendRequest
            || $friendship->trashed()
            || $friendship->status !== FriendRequestStatus::Accepted) {
            throw ValidationException::withMessages([
                'user_id' => 'You are not friends with this user.',
            ]);
        }

        $this->repository->delete($friendship);
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

    /**
     * Record the receiver's decision against a pending request.
     */
    private function respond(FriendRequest $friendRequest, FriendRequestStatus $status): FriendRequest
    {
        return $this->repository->update($friendRequest, [
            'status' => $status,
            'responded_at' => now(),
        ])->load(['sender', 'receiver']);
    }
}
