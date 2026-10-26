<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\FriendRequest;
use Illuminate\Foundation\Http\FormRequest;

class IndexFriendSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FriendRequest::class) === true;
    }
}
