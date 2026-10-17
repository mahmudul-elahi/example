<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexBlogCategoryRequest;
use App\Http\Requests\Api\V1\Admin\StoreBlogCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateBlogCategoryRequest;
use App\Http\Resources\V1\BlogCategoryResource;
use App\Models\BlogCategory;
use App\Services\BlogCategoryServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Admin - Blog Category', weight: 130)]
class BlogCategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BlogCategoryServiceInterface $categories,
    ) {}

    public function index(IndexBlogCategoryRequest $request): JsonResponse
    {
        $categories = $this->categories->paginate($request->perPage(), $request->search());

        return $this->paginated($categories, BlogCategoryResource::collection($categories->items()));
    }

    public function store(StoreBlogCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());

        return $this->created(new BlogCategoryResource($this->categories->find($category->id)));
    }

    public function show(BlogCategory $blogCategory): JsonResponse
    {
        return $this->success(new BlogCategoryResource($this->categories->find($blogCategory->id)));
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blogCategory): JsonResponse
    {
        $this->categories->update($blogCategory, $request->validated());

        return $this->success(new BlogCategoryResource($this->categories->find($blogCategory->id)));
    }

    public function destroy(BlogCategory $blogCategory): JsonResponse
    {
        $this->categories->delete($blogCategory);

        return $this->success(null, 'Blog category deleted successfully');
    }
}
