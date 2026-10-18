<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Traits\ResolvesRouteModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class LikeRequest extends FormRequest
{
    use ResolvesRouteModel;

    public function authorize(): bool
    {
        $likeable = $this->routeModel();

        return $likeable instanceof Model
            && $this->user() !== null
            && Gate::allows('view', $likeable);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
