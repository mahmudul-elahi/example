<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\Comment;
use App\Traits\ResolvesRouteModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    use ResolvesRouteModel;

    private ?Comment $resolvedComment = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('update', $this->comment());
    }

    /**
     * The comment addressed by content type and comment id.
     *
     * @throws ModelNotFoundException when the content type is unknown or the comment belongs to another type
     */
    public function comment(): Comment
    {
        if ($this->resolvedComment instanceof Comment) {
            return $this->resolvedComment;
        }

        $comment = Comment::query()->findOrFail((int) $this->route('comment'));

        if ($comment->commentable_type !== $this->routeContentType()->modelClass()) {
            throw new ModelNotFoundException;
        }

        return $this->resolvedComment = $comment;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
