<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Public;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', BlogPost::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }

    public function search(): ?string
    {
        return $this->filled('search') ? (string) $this->input('search') : null;
    }
}
