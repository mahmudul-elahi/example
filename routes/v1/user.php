<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\User\CommentController;
use App\Http\Controllers\Api\V1\User\FriendRequestController;
use App\Http\Controllers\Api\V1\User\LikeController;
use App\Http\Controllers\Api\V1\User\PetMemorialController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('memorials', PetMemorialController::class)->except(['index', 'show']);

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
});
