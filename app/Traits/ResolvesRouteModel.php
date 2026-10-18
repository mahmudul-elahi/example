<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesRouteModel
{
    /**
     * The Eloquent model bound to the current route.
     *
     * Comment and like routes bind exactly one model (the commentable or the
     * likeable), so requests resolve their target from the route instead of
     * naming a resource, and one request class serves every commentable model.
     */
    protected function routeModel(): ?Model
    {
        return collect($this->route()?->parameters() ?? [])
            ->first(fn (mixed $parameter): bool => $parameter instanceof Model);
    }

    /**
     * The content type named by the {type} route parameter.
     *
     * @throws ModelNotFoundException when the parameter is not a known content type
     */
    protected function routeContentType(): ContentType
    {
        $type = ContentType::tryFrom((string) $this->route('type'));

        if (! $type instanceof ContentType) {
            throw new ModelNotFoundException;
        }

        return $type;
    }
}
