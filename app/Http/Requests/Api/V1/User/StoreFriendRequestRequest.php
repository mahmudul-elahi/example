<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFriendRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FriendRequest::class) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * The user the request is addressed to.
     */
    public function receiver(): User
    {
        return User::query()->findOrFail((int) $this->validated('user_id'));
    }
}
