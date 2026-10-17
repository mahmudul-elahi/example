<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Enums\PetType;
use App\Enums\PostStatus;
use App\Enums\VisibilityStatus;
use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetMemorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PetMemorial $memorial */
        $memorial = $this->route('memorial');

        return $this->user()->can('update', $memorial);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'pet_name' => ['sometimes', 'string', 'max:255'],
            'pet_type' => ['sometimes', 'nullable', 'string', Rule::in(PetType::values())],
            'pet_born_at' => ['sometimes', 'nullable', 'date'],
            'pet_passed_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:pet_born_at'],
            'cover_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'string', Rule::in([PostStatus::Draft->value, PostStatus::Published->value])],
            'visibility' => ['sometimes', 'string', Rule::in(VisibilityStatus::values())],
        ];
    }
}
