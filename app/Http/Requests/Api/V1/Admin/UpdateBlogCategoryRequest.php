<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\BlogCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BlogCategory $category */
        $category = $this->route('blog_category');

        return $this->user()->can('update', $category);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $category = $this->route('blog_category');
        $categoryId = $category instanceof BlogCategory ? $category->id : $category;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('blog_categories', 'name')->ignore($categoryId)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
