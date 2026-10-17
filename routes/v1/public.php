<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Public\BlogPostController;
use App\Http\Controllers\Api\V1\Public\CommentController;
use App\Http\Controllers\Api\V1\Public\PetMemorialController;
use App\Http\Controllers\Api\V1\Public\ProductController;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

/*
| Public reads opt into caching with #[Cache] / #[NoCache] on the controller
| action. The CacheResponse middleware only applies when the route carries it,
| so it is attached to every public route below and the per-action attributes
| decide the lifetime (or opt out of caching).
*/
Route::middleware(CacheResponse::class)->group(function (): void {
    Route::apiResource('memorials', PetMemorialController::class)->only(['index', 'show']);
    Route::get('memorials/{memorial}/comments', [CommentController::class, 'index']);

    Route::get('blog', [BlogPostController::class, 'index']);
    Route::get('blog/{blog_post}', [BlogPostController::class, 'show']);

    Route::get('shop', [ProductController::class, 'index']);
    Route::get('shop/{product}', [ProductController::class, 'show']);
});
