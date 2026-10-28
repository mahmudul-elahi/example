<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\User\BlockedUserController;
use App\Http\Controllers\Api\V1\User\CommentController;
use App\Http\Controllers\Api\V1\User\ConversationController;
use App\Http\Controllers\Api\V1\User\FriendRequestController;
use App\Http\Controllers\Api\V1\User\LikeController;
use App\Http\Controllers\Api\V1\User\MessageController;
use App\Http\Controllers\Api\V1\User\NotificationController;
use App\Http\Controllers\Api\V1\User\PetMemorialController;
use App\Http\Controllers\Api\V1\User\StoryController;
use App\Http\Controllers\Api\V1\User\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
    Route::get('memorials/mine', [PetMemorialController::class, 'mine']);
    Route::apiResource('memorials', PetMemorialController::class)->except(['show']);

    Route::get('memorials/{memorial}/comments', [CommentController::class, 'indexMemorial']);
    Route::post('memorials/{memorial}/comments', [CommentController::class, 'storeMemorial']);
    Route::post('blog/{blog_post}/comments', [CommentController::class, 'storeBlogPost']);
    Route::post('shop/{product}/comments', [CommentController::class, 'storeProduct']);
    Route::put('comments/{type}/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{type}/{comment}', [CommentController::class, 'destroy']);

    Route::post('memorials/{memorial}/like', [LikeController::class, 'storeMemorial']);
    Route::post('blog/{blog_post}/like', [LikeController::class, 'storeBlogPost']);
    Route::post('shop/{product}/like', [LikeController::class, 'storeProduct']);
    Route::delete('likes/{type}/{id}', [LikeController::class, 'destroy']);

    Route::get('friend-requests', [FriendRequestController::class, 'index']);
    Route::get('friend-requests/count', [FriendRequestController::class, 'count']);
    Route::post('friend-requests', [FriendRequestController::class, 'store']);
    Route::post('friend-requests/{friend_request}/accept', [FriendRequestController::class, 'accept']);
    Route::post('friend-requests/{friend_request}/reject', [FriendRequestController::class, 'reject']);
    Route::delete('friend-requests/{friend_request}', [FriendRequestController::class, 'destroy']);
    Route::get('friends', [FriendRequestController::class, 'friends']);
    Route::get('friends/suggestions', [FriendRequestController::class, 'suggestions']);
    Route::delete('friends/{friend}', [FriendRequestController::class, 'unfriend']);

    Route::get('stories', [StoryController::class, 'index']);
    Route::get('stories/mine', [StoryController::class, 'mine']);
    Route::post('stories', [StoryController::class, 'store']);
    Route::get('stories/{story}/media', [StoryController::class, 'media']);
    Route::delete('stories/{story}', [StoryController::class, 'destroy']);

    Route::get('users/blocked', [BlockedUserController::class, 'index']);
    Route::post('users/{user}/block', [BlockedUserController::class, 'block']);
    Route::delete('users/{user}/block', [BlockedUserController::class, 'unblock']);

    Route::post('profile/avatar', [UserProfileController::class, 'updateAvatar']);
    Route::get('users/{user}', [UserProfileController::class, 'show']);
    Route::get('users/{user}/memorials', [UserProfileController::class, 'memorials']);
    Route::get('users/{user}/stories', [UserProfileController::class, 'stories']);

    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy']);
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'read']);

    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::delete('conversations/{conversation}/messages/{message}', [MessageController::class, 'destroy']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/count', [NotificationController::class, 'count']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
});
