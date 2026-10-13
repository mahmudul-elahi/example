<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', Rule::in(Roles::values())],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }

    public function search(): ?string
    {
        return $this->filled('search') ? (string) $this->input('search') : null;
    }

    public function role(): ?Roles
    {
        return $this->filled('role') ? Roles::tryFrom((string) $this->input('role')) : null;
    }
}
