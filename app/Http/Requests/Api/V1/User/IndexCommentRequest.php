<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Traits\ResolvesRouteModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexCommentRequest extends FormRequest
{
    use ResolvesRouteModel;

    public function authorize(): bool
    {
        $commentable = $this->routeModel();

        return $commentable instanceof Model && Gate::allows('view', $commentable);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }

    public function cursor(): ?string
    {
        return $this->filled('cursor') ? (string) $this->input('cursor') : null;
    }
}
