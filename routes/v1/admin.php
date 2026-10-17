<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\BlogCategoryController;
use App\Http\Controllers\Api\V1\Admin\BlogPostController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index']);
Route::apiResource('users', UserController::class);
Route::apiResource('blog-posts', BlogPostController::class);
Route::apiResource('blog-categories', BlogCategoryController::class);
Route::apiResource('products', ProductController::class);
