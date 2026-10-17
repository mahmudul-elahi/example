<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Public\BlogPostController;
use App\Http\Controllers\Api\V1\Public\CommentController;
use App\Http\Controllers\Api\V1\Public\PetMemorialController;
use App\Http\Controllers\Api\V1\Public\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('memorials', PetMemorialController::class)->only(['index', 'show']);
Route::get('memorials/{memorial}/comments', [CommentController::class, 'index']);

Route::get('blog', [BlogPostController::class, 'index']);
Route::get('blog/{blog_post}', [BlogPostController::class, 'show']);

Route::get('shop', [ProductController::class, 'index']);
Route::get('shop/{product}', [ProductController::class, 'show']);
