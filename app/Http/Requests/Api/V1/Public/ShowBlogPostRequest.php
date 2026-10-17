<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Public;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BlogPost $post */
        $post = $this->route('blog_post');

        return Gate::allows('view', $post);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
