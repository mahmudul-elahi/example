<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class FriendRequestSeeder extends Seeder
{
    /**
     * How many accepted friendships each user starts.
     */
    private const int FRIENDS_PER_USER = 3;

    /**
     * How many users receive a pending request from outside their circle.
     */
    private const int PENDING_REQUESTS = 10;

    /**
     * How far ahead to reach for the sender of a pending request.
     */
    private const int PENDING_REQUEST_OFFSET = 10;

    /**
     * How many requests end in a rejection.
     */
    private const int REJECTED_REQUESTS = 5;

    /**
     * How far ahead to reach for the sender of a rejected request.
     */
    private const int REJECTED_REQUEST_OFFSET = 16;

    /**
     * Seed a connected friend graph: every user gets a handful of accepted
     * friendships, plus some pending and rejected requests.
     */
    public function run(): void
    {
        if (FriendRequest::withTrashed()->exists()) {
            return;
        }

        $users = User::query()->orderBy('id')->get()->values();
        $count = $users->count();

        if ($count < 2) {
            return;
        }

        $pairs = [];

        foreach ($users as $index => $sender) {
            for ($offset = 1; $offset <= self::FRIENDS_PER_USER; $offset++) {
                $this->createRequest(
                    $pairs,
                    $sender,
                    $users[($index + $offset) % $count],
                    FriendRequestStatus::Accepted,
                );
            }
        }

        foreach (range(0, self::PENDING_REQUESTS - 1) as $index) {
            $this->createRequest(
                $pairs,
                $users[($index + self::PENDING_REQUEST_OFFSET) % $count],
                $users[$index % $count],
                FriendRequestStatus::Pending,
            );
        }

        foreach (range(0, self::REJECTED_REQUESTS - 1) as $index) {
            $this->createRequest(
                $pairs,
                $users[($index + self::REJECTED_REQUEST_OFFSET) % $count],
                $users[$index % $count],
                FriendRequestStatus::Rejected,
            );
        }
    }

    /**
     * Create one request, skipping any pair that is already connected.
     *
     * @param  array<string, true>  $pairs
     */
    private function createRequest(array &$pairs, User $sender, User $receiver, FriendRequestStatus $status): void
    {
        if ($sender->is($receiver)) {
            return;
        }

        $key = min($sender->id, $receiver->id).':'.max($sender->id, $receiver->id);

        if (isset($pairs[$key])) {
            return;
        }

        $pairs[$key] = true;

        $friendRequest = FriendRequest::factory()
            ->from($sender)
            ->to($receiver)
            ->create();

        if ($status === FriendRequestStatus::Pending) {
            return;
        }

        $friendRequest->update([
            'status' => $status,
            'responded_at' => now(),
        ]);
    }
}
