<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'for_everyone' => ['nullable', 'boolean'],
        ];
    }

    public function forEveryone(): bool
    {
        return $this->boolean('for_everyone');
    }
}
