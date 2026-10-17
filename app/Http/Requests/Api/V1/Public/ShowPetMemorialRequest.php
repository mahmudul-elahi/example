<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Public;

use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowPetMemorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PetMemorial $memorial */
        $memorial = $this->route('memorial');

        return Gate::allows('view', $memorial);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
