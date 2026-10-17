<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return $comment instanceof Comment
            && $this->user() !== null
            && $this->user()->can('delete', $comment);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
