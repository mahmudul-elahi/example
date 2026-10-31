<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\V1\CommentResource;
use App\Http\Resources\V1\PetMemorialResource;
use App\Http\Resources\V1\StoryResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

class UserDataExportService implements UserDataExportServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function exportData(User $user): array
    {
        $memorials = $user->petMemorials()->with(['images', 'user'])->get();
        $stories = $user->stories()->get();
        $comments = $user->comments()->get();

        return [
            'exported_at' => now()->toIso8601String(),
            'profile' => (new UserResource($user))->resolve(),
            'memorials' => PetMemorialResource::collection($memorials)->resolve(),
            'stories' => StoryResource::collection($stories)->resolve(),
            'comments' => CommentResource::collection($comments)->resolve(),
        ];
    }
}
