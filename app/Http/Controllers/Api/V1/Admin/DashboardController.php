<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DashboardResource;
use App\Services\DashboardServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Admin - Dashboard', weight: 110)]
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DashboardServiceInterface $dashboard,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(new DashboardResource($this->dashboard->summary()));
    }
}
