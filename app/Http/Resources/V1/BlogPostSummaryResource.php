<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\BlogPost;
use Illuminate\Http\Request;

/**
 * The public blog index payload: everything from the full resource except the
 * article body, which is large and only needed on the detail page.
 *
 * @mixin BlogPost
 */
class BlogPostSummaryResource extends BlogPostResource
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
