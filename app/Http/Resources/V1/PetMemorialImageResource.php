<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\PetMemorialImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetMemorialImage
 *
 * @property string $url
 */
class PetMemorialImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
        ];
    }
}

