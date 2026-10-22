<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * The public shop index payload: everything from the full resource except the
 * long product description, which is only needed on the detail page.
 *
 * @mixin Product
 */
class ProductSummaryResource extends ProductResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        unset($data['description']);

        return $data;
    }
}
