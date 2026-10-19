<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\FriendRequest;
use Illuminate\Foundation\Http\FormRequest;

class DeleteFriendRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $friendRequest = $this->friendRequest();

        return $friendRequest instanceof FriendRequest
            && $this->user()?->can('cancel', $friendRequest) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * The request being cancelled.
     */
    public function friendRequest(): ?FriendRequest
    {
        $friendRequest = $this->route('friend_request');

        return $friendRequest instanceof FriendRequest ? $friendRequest : null;
    }
}
