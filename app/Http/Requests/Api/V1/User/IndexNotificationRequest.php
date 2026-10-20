<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;

class IndexNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Notification::class) === true;
    }

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
        return (int) $this->input('per_page', 15);
    }

    public function cursor(): ?string
    {
        return $this->filled('cursor') ? (string) $this->input('cursor') : null;
    }
}
