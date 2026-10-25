<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Enums\PetType;
use App\Enums\PostStatus;
use App\Enums\VisibilityStatus;
use App\Models\PetMemorial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'body' => ['required', 'string'],
            'pet_name' => ['required', 'string', 'max:255'],
            'pet_type' => ['sometimes', 'nullable', 'string', Rule::in(PetType::values())],
            'pet_born_at' => ['sometimes', 'nullable', 'date'],
            'pet_passed_at' => ['sometimes', 'nullable', 'date'],
            'cover_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'string', Rule::in([PostStatus::Draft->value, PostStatus::Published->value])],
            'visibility' => ['sometimes', 'string', Rule::in(VisibilityStatus::values())],
        ];
    }

    /**
     * Ensure the passing date never precedes the birth date when both are sent.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['pet_born_at', 'pet_passed_at'])) {
                    return;
                }

                $bornAt = $this->input('pet_born_at');
                $passedAt = $this->input('pet_passed_at');

                if (
                    $bornAt !== null
                    && $passedAt !== null
                    && Date::parse((string) $passedAt)->lt(Date::parse((string) $bornAt))
                ) {
                    $validator->errors()->add(
                        'pet_passed_at',
                        'The pet passed date must be on or after the pet born date.',
                    );
                }
            },
        ];
    }
}
