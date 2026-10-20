<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;

class ReadNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notification = $this->route('notification');

        return $notification instanceof Notification
            && $this->user()?->can('markAsRead', $notification) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
