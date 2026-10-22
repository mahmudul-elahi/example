<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\PetMemorial;
use Illuminate\Http\Request;

/**
 * The public memorial index payload: everything from the full resource except
 * the memorial body, which is only needed on the detail page.
 *
 * @mixin PetMemorial
 */
class PetMemorialSummaryResource extends PetMemorialResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        unset($data['body']);

        return $data;
    }
}
