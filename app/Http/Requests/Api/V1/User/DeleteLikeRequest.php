<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Traits\ResolvesRouteModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteLikeRequest extends FormRequest
{
    use ResolvesRouteModel;

    private ?Model $resolvedLikeable = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && Gate::allows('view', $this->likeable());
    }

    /**
     * The model the like belongs to, addressed by content type and id.
     *
     * @throws ModelNotFoundException when the content type is unknown or the model is gone
     */
    public function likeable(): Model
    {
        if ($this->resolvedLikeable instanceof Model) {
            return $this->resolvedLikeable;
        }

        $modelClass = $this->routeContentType()->modelClass();

        return $this->resolvedLikeable = $modelClass::query()->findOrFail((int) $this->route('id'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
