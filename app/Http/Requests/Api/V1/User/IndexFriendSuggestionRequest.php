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

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}
