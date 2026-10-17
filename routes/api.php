<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group([
    __DIR__.'/v1/auth.php',
    __DIR__.'/v1/public.php',
    __DIR__.'/v1/user.php',
]);

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group([
        __DIR__.'/v1/admin.php',
    ]);
