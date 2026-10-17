<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class LikeMemorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $memorial = $this->route('memorial');

        return $memorial instanceof PetMemorial
            && $this->user() !== null
            && Gate::allows('view', $memorial);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
