<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;

class DeletePetMemorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PetMemorial $memorial */
        $memorial = $this->route('memorial');

        return $this->user()->can('delete', $memorial);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
