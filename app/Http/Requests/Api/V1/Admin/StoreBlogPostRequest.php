<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\PostStatus;
use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BlogPost::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cover_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'blog_category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('blog_categories', 'id')],
            'status' => ['sometimes', 'string', Rule::in(PostStatus::values())],
            'is_featured' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
