<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\User\CommentController;
use App\Http\Controllers\Api\V1\User\LikeController;
use App\Http\Controllers\Api\V1\User\PetMemorialController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('memorials', PetMemorialController::class)->except(['index', 'show']);

    Route::post('memorials/{memorial}/comments', [CommentController::class, 'store']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('memorials/{memorial}/like', [LikeController::class, 'store']);
    Route::delete('memorials/{memorial}/like', [LikeController::class, 'destroy']);
});
