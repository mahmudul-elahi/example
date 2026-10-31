<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'string', Rule::in(['light', 'dark', 'system'])],
            'language' => ['sometimes', 'string', 'max:10'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'privacy' => ['sometimes', 'array'],
            'privacy.profile_visibility' => ['sometimes', 'string', Rule::in(['public', 'private'])],
            'privacy.default_memorial_visibility' => ['sometimes', 'string', Rule::in(['public', 'private'])],
            'privacy.allow_memorial_comments' => ['sometimes', 'boolean'],
            'notifications' => ['sometimes', 'array'],
            'notifications.email_comments' => ['sometimes', 'boolean'],
            'notifications.email_likes' => ['sometimes', 'boolean'],
            'notifications.email_friend_requests' => ['sometimes', 'boolean'],
            'notifications.email_marketing' => ['sometimes', 'boolean'],
            'notifications.in_app_alerts' => ['sometimes', 'boolean'],
        ];
    }
}
