<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Enums\PetType;
use App\Enums\PostStatus;
use App\Enums\VisibilityStatus;
use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePetMemorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PetMemorial::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'pet_name' => ['required', 'string', 'max:255'],
            'pet_type' => ['sometimes', 'nullable', 'string', Rule::in(PetType::values())],
            'pet_born_at' => ['sometimes', 'nullable', 'date'],
            'pet_passed_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:pet_born_at'],
            'cover_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'string', Rule::in([PostStatus::Draft->value, PostStatus::Published->value])],
            'visibility' => ['sometimes', 'string', Rule::in(VisibilityStatus::values())],
        ];
    }
}
