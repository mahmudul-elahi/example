<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The generic envelope shared by every in-app notification.
 *
 * Type-specific fields live under `data`, so adding a notification type never
 * requires changing this resource. Clients switch on `type`.
 *
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => (string) $this->type,
            'message' => isset($payload['message']) ? (string) $payload['message'] : null,
            'data' => $this->payloadData($payload),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * The payload shape depends on the notification type, so it is documented
     * as a free-form object rather than pretending every type shares fields.
     *
     * @param  array<string, mixed>  $payload
     */
    private function payloadData(array $payload): object
    {
        return (object) ($payload['data'] ?? []);
    }
}
