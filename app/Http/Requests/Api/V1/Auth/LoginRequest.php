<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'code' => ['sometimes', 'nullable', 'string'],
            'recovery_code' => ['sometimes', 'nullable', 'string'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
