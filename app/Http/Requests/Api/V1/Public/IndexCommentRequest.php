<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Public;

use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $memorial = $this->route('memorial');

        return $memorial instanceof PetMemorial && Gate::allows('view', $memorial);
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
