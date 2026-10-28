<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class IndexMessageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
        ];
    }

    public function perPage(): int
    {
        return min(max($this->integer('per_page', 25), 1), 100);
    }

    public function cursor(): ?string
    {
        $cursor = $this->string('cursor')->toString();

        return $cursor !== '' ? $cursor : null;
    }
}
