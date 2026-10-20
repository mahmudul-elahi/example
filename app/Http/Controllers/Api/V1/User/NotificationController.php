<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\IndexNotificationRequest;
use App\Http\Requests\Api\V1\User\ReadNotificationRequest;
use App\Http\Resources\V1\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * The authenticated user's in-app notifications. Friend request events create
 * these through FriendRequestObserver.
 */
#[Group('User - Notifications', weight: 95)]
class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationServiceInterface $notifications,
    ) {}

    public function index(IndexNotificationRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $notifications = $this->notifications->paginate($user, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($notifications, NotificationResource::collection($notifications->items()));
    }

    public function count(#[CurrentUser] User $user): JsonResponse
    {
        return $this->success(['unread_count' => $this->notifications->unreadCount($user)]);
    }

    public function read(ReadNotificationRequest $request, Notification $notification): JsonResponse
    {
        $this->notifications->markAsRead($notification);

        return $this->success(new NotificationResource($notification), 'Notification marked as read');
    }

    public function readAll(#[CurrentUser] User $user): JsonResponse
    {
        return $this->success(
            ['marked_read' => $this->notifications->markAllAsRead($user)],
            'Notifications marked as read',
        );
    }
}
